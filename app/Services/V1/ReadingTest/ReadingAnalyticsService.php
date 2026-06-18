<?php

namespace App\Services\V1\ReadingTest;

use App\Models\Classes;
use App\Models\ReadingSubmission;
use App\Models\ReadingTask;
use App\Models\Test;
use App\Services\V1\Test\DualAttemptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ReadingAnalyticsService
{
    public function getTaskAnalytics(ReadingTask|Test $task, Request $request): array
    {
        $query = $this->submissionsQuery($task);
        $query->with(['answers', 'student', 'assignment.class']);

        $this->applyFilters($query, $request);

        $submissions = $query->get();
        $teacherSubmissions = DualAttemptService::filterForTeacherView($submissions);

        $completedSubmissions = $teacherSubmissions->filter(
            fn ($s) => in_array($s->status, ['completed', 'submitted'], true) && $s->submitted_at !== null
        );

        $analytics = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'class_name' => $this->resolveClassName($task),
            'created_at' => $task->created_at->format('d M Y'),
            'total_submissions' => $teacherSubmissions->count(),
            'completed_submissions' => $completedSubmissions->count(),
            'in_progress_submissions' => $teacherSubmissions->whereNotIn('status', ['completed', 'submitted'])->count(),
            'completion_rate' => $teacherSubmissions->count() > 0
                ? round(($completedSubmissions->count() / $teacherSubmissions->count()) * 100, 2)
                : 0,
        ];

        if ($completedSubmissions->count() > 0) {
            $scores = $completedSubmissions->pluck('percentage')->filter(fn ($s) => $s !== null);

            $analytics = array_merge($analytics, [
                'average_score' => round($scores->avg(), 1),
                'highest_score' => round($scores->max(), 1),
                'lowest_score' => round($scores->min(), 1),
            ]);
        } else {
            $analytics = array_merge($analytics, [
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
            ]);
        }

        $analytics['question_type_performance'] = $this->analyzeQuestionTypePerformance($task, $teacherSubmissions);

        $leaderboard = $this->getLeaderboard($teacherSubmissions, $request);
        $analytics['leaderboard'] = $leaderboard['items'];
        $analytics['leaderboard_meta'] = $leaderboard['meta'];

        return $analytics;
    }

    private function submissionsQuery(ReadingTask|Test $task): Builder
    {
        if ($task instanceof ReadingTask) {
            return ReadingSubmission::where('reading_task_id', $task->id);
        }

        return ReadingSubmission::where('test_id', $task->id);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $statuses = array_map('trim', explode(',', strtolower($request->status)));
            $query->where(function ($q) use ($statuses) {
                foreach ($statuses as $status) {
                    if ($status === 'pending') {
                        $q->orWhere(fn ($subQ) => $subQ
                            ->whereNull('submitted_at')
                            ->orWhereIn('status', ['in_progress', 'pending']));
                    } elseif ($status === 'ontime') {
                        $q->orWhere(function ($subQ) {
                            $subQ->whereNotNull('submitted_at')
                                ->whereIn('status', ['completed', 'submitted'])
                                ->where(function ($timeQ) {
                                    $timeQ->whereDoesntHave('assignment')
                                        ->orWhereHas('assignment', fn ($a) => $a
                                            ->where(fn ($d) => $d
                                                ->whereNull('due_date')
                                                ->orWhereColumn('reading_submissions.submitted_at', '<=', 'assignments.due_date')));
                                });
                        });
                    } elseif ($status === 'late') {
                        $q->orWhere(function ($subQ) {
                            $subQ->whereNotNull('submitted_at')
                                ->whereIn('status', ['completed', 'submitted'])
                                ->whereHas('assignment', fn ($a) => $a
                                    ->whereNotNull('due_date')
                                    ->whereColumn('reading_submissions.submitted_at', '>', 'assignments.due_date'));
                        });
                    }
                }
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('submitted_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('submitted_at', '<=', $request->to);
        }

        if ($request->filled('minScore')) {
            $query->where('percentage', '>=', (float) $request->minScore);
        }

        if ($request->filled('maxScore')) {
            $query->where('percentage', '<=', (float) $request->maxScore);
        }
    }

    private function resolveClassName(ReadingTask|Test $task): string
    {
        if ($task->class_id) {
            $class = Classes::find($task->class_id);
            if ($class) {
                return $class->name;
            }
        }

        if ($task instanceof ReadingTask) {
            $assignment = $task->assignments()->with('class')->first();
            if ($assignment?->class) {
                return $assignment->class->name;
            }
        } else {
            $assignment = $task->assignments()->with('class')->first();
            if ($assignment?->class) {
                return $assignment->class->name;
            }
        }

        return 'No Class Assigned';
    }

    private function analyzeQuestionTypePerformance(ReadingTask|Test $task, Collection $submissions): array
    {
        $typeMap = $task instanceof ReadingTask
            ? $this->buildQuestionTypeMapFromReadingTask($task)
            : $this->buildQuestionTypeMapFromTest($task);

        $stats = [];

        foreach ($submissions as $submission) {
            foreach ($submission->answers as $answer) {
                $questionId = $answer->reading_task_question_id ?? $answer->question_id;
                $type = $typeMap[(string) $questionId] ?? 'general';

                if (!isset($stats[$type])) {
                    $stats[$type] = ['correct' => 0, 'total' => 0];
                }

                $stats[$type]['total']++;
                if ($answer->is_correct) {
                    $stats[$type]['correct']++;
                }
            }
        }

        $result = [];
        foreach ($stats as $type => $data) {
            $result[] = [
                'type' => $type,
                'name' => ucwords(str_replace('_', ' ', $type)),
                'accuracy' => $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0,
                'total_questions' => $data['total'],
            ];
        }

        return $result;
    }

    private function buildQuestionTypeMapFromReadingTask(ReadingTask $task): array
    {
        $map = [];

        foreach ($task->passages ?? [] as $passage) {
            foreach ($passage['question_groups'] ?? [] as $group) {
                foreach ($group['questions'] ?? [] as $question) {
                    $type = $question['question_type'] ?? 'general';
                    $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');

                    if ($parentKey !== '') {
                        $map[$parentKey] = $type;
                    }

                    if (($type === 'note_completion' || $type === 'table_completion') && $parentKey !== '') {
                        $blanks = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                        if (is_array($blanks)) {
                            foreach ($blanks as $blank) {
                                $blankKey = $blank['option_key'] ?? null;
                                if ($blankKey !== null) {
                                    $map["{$parentKey}-blank-{$blankKey}"] = $type;
                                }
                            }
                        }
                    }

                    foreach ($question['items'] ?? [] as $idx => $item) {
                        $itemNum = $item['question_number'] ?? ($idx + 1);
                        $itemKey = (string) ($item['id'] ?? ($parentKey !== '' ? "{$parentKey}-item-{$itemNum}" : $itemNum));
                        if ($itemKey !== '') {
                            $map[$itemKey] = $item['question_type'] ?? "{$type}_item";
                        }
                    }
                }
            }
        }

        return $map;
    }

    private function buildQuestionTypeMapFromTest(Test $test): array
    {
        $map = [];
        $test->loadMissing('passages.questionGroups.questions');

        foreach ($test->passages as $passage) {
            foreach ($passage->questionGroups as $group) {
                foreach ($group->questions as $question) {
                    $map[(string) $question->id] = $question->question_type ?? 'general';
                }
            }
        }

        return $map;
    }

    private function getLeaderboard(Collection $submissions, Request $request): array
    {
        $search = $request->search;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('pageSize', $request->query('per_page', 10)));

        $leaderboard = $submissions->groupBy('student_id')->map(function ($studentWork) {
            $official = $studentWork->firstWhere('attempt_number', DualAttemptService::OFFICIAL_ATTEMPT)
                ?? $studentWork->sortBy('attempt_number')->first();
            $bestAttempt = $official;

            $status = 'ontime';
            if (!$bestAttempt->submitted_at) {
                $status = 'pending';
            } elseif (
                $bestAttempt->assignment?->due_date &&
                $bestAttempt->submitted_at->gt($bestAttempt->assignment->due_date)
            ) {
                $status = 'late';
            }

            return [
                'id' => $bestAttempt->id,
                'student_name' => $bestAttempt->student->name ?? 'Unknown Student',
                'submission_date' => $bestAttempt->submitted_at
                    ? $bestAttempt->submitted_at->format('d M Y')
                    : ($bestAttempt->created_at ? $bestAttempt->created_at->format('d M Y') : '-'),
                'status' => in_array($bestAttempt->status, ['completed', 'submitted'], true) ? $status : 'pending',
                'score' => round((float) ($bestAttempt->percentage ?? 0), 1),
                'type' => 'reading',
            ];
        })->sortByDesc('score')->values();

        if ($search) {
            $needle = strtolower($search);
            $leaderboard = $leaderboard->filter(
                fn ($entry) => str_contains(strtolower($entry['student_name']), $needle)
            )->values();
        }

        $total = $leaderboard->count();

        return [
            'items' => $leaderboard->slice(($page - 1) * $perPage, $perPage)->values()->toArray(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }
}
