<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\Classes;
use App\Models\SpeakingSubmission;
use App\Models\SpeakingTask;
use App\Models\Test;
use App\Services\V1\Test\DualAttemptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SpeakingAnalyticsService
{
    private const SKILL_LABELS = [
        'fluency' => 'Fluency & Coherence',
        'pronunciation' => 'Pronunciation',
        'vocabulary' => 'Lexical Resource',
        'grammar' => 'Grammatical Range',
    ];

    public function getTaskAnalytics(SpeakingTask|Test $task, Request $request): array
    {
        $query = $this->submissionsQuery($task);
        $query->with(['review', 'student', 'assignment.class']);

        $this->applyFilters($query, $request);

        $submissions = $query->get();
        $teacherSubmissions = DualAttemptService::filterForTeacherView($submissions);
        $reviewedSubmissions = $teacherSubmissions->filter(
            fn ($s) => $s->review && $s->review->total_score !== null
        );

        $analytics = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'class_name' => $this->resolveClassName($task),
            'created_at' => $task->created_at->format('d M Y'),
            'total_submissions' => $teacherSubmissions->count(),
            'completed_submissions' => $reviewedSubmissions->count(),
            'in_progress_submissions' => $teacherSubmissions->whereNotIn('status', ['submitted', 'completed', 'reviewed'])->count(),
            'completion_rate' => $teacherSubmissions->count() > 0
                ? round(($reviewedSubmissions->count() / $teacherSubmissions->count()) * 100, 2)
                : 0,
        ];

        if ($reviewedSubmissions->count() > 0) {
            $scores = $reviewedSubmissions->map(fn ($s) => (float) $s->review->total_score);

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

        $analytics['question_type_performance'] = $this->analyzeSkillPerformance($reviewedSubmissions);

        $leaderboard = $this->getLeaderboard($teacherSubmissions, $request);
        $analytics['leaderboard'] = $leaderboard['items'];
        $analytics['leaderboard_meta'] = $leaderboard['meta'];

        return $analytics;
    }

    private function submissionsQuery(SpeakingTask|Test $task): Builder
    {
        if ($task instanceof SpeakingTask) {
            return SpeakingSubmission::where('speaking_task_id', $task->id);
        }

        return SpeakingSubmission::where('test_id', $task->id);
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
                            ->orWhereIn('status', ['to_do', 'in_progress', 'submitted']));
                    } elseif ($status === 'ontime') {
                        $q->orWhere(function ($subQ) {
                            $subQ->whereNotNull('submitted_at')
                                ->where(function ($timeQ) {
                                    $timeQ->whereDoesntHave('assignment')
                                        ->orWhereHas('assignment', fn ($a) => $a
                                            ->where(fn ($d) => $d
                                                ->whereNull('due_date')
                                                ->orWhereColumn('speaking_submissions.submitted_at', '<=', 'assignments.due_date')));
                                });
                        });
                    } elseif ($status === 'late') {
                        $q->orWhere(function ($subQ) {
                            $subQ->whereNotNull('submitted_at')
                                ->whereHas('assignment', fn ($a) => $a
                                    ->whereNotNull('due_date')
                                    ->whereColumn('speaking_submissions.submitted_at', '>', 'assignments.due_date'));
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

        if ($request->filled('minScore') || $request->filled('maxScore')) {
            $query->whereHas('review', function ($q) use ($request) {
                if ($request->filled('minScore')) {
                    $q->where('total_score', '>=', (float) $request->minScore);
                }
                if ($request->filled('maxScore')) {
                    $q->where('total_score', '<=', (float) $request->maxScore);
                }
            });
        }
    }

    private function resolveClassName(SpeakingTask|Test $task): string
    {
        if ($task->class_id) {
            $class = Classes::find($task->class_id);
            if ($class) {
                return $class->name;
            }
        }

        $assignment = $task->assignments()->with('class')->first();
        if ($assignment?->class) {
            return $assignment->class->name;
        }

        return 'No Class Assigned';
    }

    private function analyzeSkillPerformance(Collection $submissions): array
    {
        $totals = array_fill_keys(array_keys(self::SKILL_LABELS), 0);
        $counts = array_fill_keys(array_keys(self::SKILL_LABELS), 0);

        foreach ($submissions as $submission) {
            $skills = $submission->review?->skill_scores;
            if (!is_array($skills)) {
                continue;
            }

            foreach (self::SKILL_LABELS as $key => $label) {
                if (isset($skills[$key]) && is_numeric($skills[$key])) {
                    $totals[$key] += (float) $skills[$key];
                    $counts[$key]++;
                }
            }
        }

        $result = [];
        foreach (self::SKILL_LABELS as $key => $label) {
            $count = $counts[$key];
            $result[] = [
                'type' => $key,
                'name' => $label,
                'accuracy' => $count > 0 ? round($totals[$key] / $count, 1) : 0,
                'total_questions' => $count,
            ];
        }

        return $result;
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

            $status = 'pending';
            if ($bestAttempt->review?->total_score !== null) {
                $status = 'ontime';
                if (
                    $bestAttempt->submitted_at &&
                    $bestAttempt->assignment?->due_date &&
                    $bestAttempt->submitted_at->gt($bestAttempt->assignment->due_date)
                ) {
                    $status = 'late';
                }
            } elseif ($bestAttempt->submitted_at) {
                $status = 'ontime';
            }

            return [
                'id' => $bestAttempt->id,
                'student_name' => $bestAttempt->student->name ?? 'Unknown Student',
                'submission_date' => $bestAttempt->submitted_at
                    ? $bestAttempt->submitted_at->format('d M Y')
                    : ($bestAttempt->created_at ? $bestAttempt->created_at->format('d M Y') : '-'),
                'status' => $status,
                'score' => round((float) ($bestAttempt->review?->total_score ?? 0), 1),
                'type' => 'speaking',
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
