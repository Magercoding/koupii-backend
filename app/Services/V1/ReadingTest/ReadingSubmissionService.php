<?php

namespace App\Services\V1\ReadingTest;

use App\Models\Test;
use App\Models\ReadingTask;
use App\Models\ReadingSubmission;
use App\Models\TestQuestion;
use App\Services\V1\Test\DualAttemptService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ReadingSubmissionService
{
    private const COMPLETED_STATUSES = ['completed', 'submitted'];

    /**
     * Get student's reading test submissions
     */
    public function getStudentSubmissions(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return ReadingSubmission::with(['test:id,title,difficulty,type', 'readingTask:id,title,difficulty', 'answers'])
            ->where('student_id', $studentId)
            ->when($filters['assignment_id'] ?? null, fn($q, $assignmentId) => $q->where('assignment_id', $assignmentId))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, function($q, $type) {
                if ($type === 'reading_task') {
                    $q->whereNotNull('reading_task_id');
                } else {
                    $q->whereNotNull('test_id');
                }
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Start a new reading test attempt (Legacy Test)
     */
    public function startTest(Test $test, string $studentId, array $data): ReadingSubmission
    {
        if ($test->type !== 'reading') {
            throw new Exception('Invalid test type for reading submission');
        }

        $assignmentId = $data['assignment_id'] ?? null;

        $baseQuery = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId);

        if ($assignmentId) {
            $baseQuery->where('assignment_id', $assignmentId);
        } else {
            $baseQuery->where(function ($q) {
                $q->whereNull('assignment_id')->orWhere('assignment_id', '');
            });
        }

        $attemptNumber = isset($data['attempt_number']) && (int) $data['attempt_number'] > 0
            ? (int) $data['attempt_number']
            : DualAttemptService::resolveAttemptNumber(clone $baseQuery, self::COMPLETED_STATUSES);

        $inProgress = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return $inProgress->load(['answers', 'test']);
        }

        $existing = (clone $baseQuery)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existing) {
            if (DualAttemptService::shouldResetPracticeAttempt($existing, $attemptNumber, self::COMPLETED_STATUSES)) {
                return $this->resetPracticeSubmission($existing);
            }

            return $existing->load(['answers', 'test']);
        }

        $submission = ReadingSubmission::create([
            'test_id' => $test->id,
            'student_id' => $studentId,
            'assignment_id' => empty($assignmentId) ? null : $assignmentId,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->initializeAnswersFromTest($submission);

        if ($assignmentId) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                ->where('student_id', $studentId)
                ->first();

            if ($studentAssignment) {
                $updateData = [
                    'last_activity_at' => now(),
                    'attempt_number' => $attemptNumber,
                    'attempt_count' => max((int) $studentAssignment->attempt_count, $attemptNumber),
                    'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                ];

                if (!$studentAssignment->started_at) {
                    $updateData['started_at'] = now();
                }

                if ($attemptNumber === DualAttemptService::PRACTICE_ATTEMPT) {
                    $updateData['score'] = 0;
                    $updateData['completed_at'] = null;
                }

                $studentAssignment->update($updateData);
            }
        }

        return $submission->load(['test', 'answers']);
    }

    /**
     * Start a new reading task attempt (New ReadingTask)
     */
    public function startReadingTask(ReadingTask $task, string $studentId, array $data): ReadingSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;

        $baseQuery = ReadingSubmission::query()
            ->where('reading_task_id', $task->id)
            ->where('student_id', $studentId);

        if ($assignmentId) {
            $baseQuery->where('assignment_id', $assignmentId);
        } else {
            $baseQuery->where(function ($q) {
                $q->whereNull('assignment_id')->orWhere('assignment_id', '');
            });
        }

        $attemptNumber = isset($data['attempt_number']) && (int) $data['attempt_number'] > 0
            ? (int) $data['attempt_number']
            : DualAttemptService::resolveAttemptNumber(clone $baseQuery, self::COMPLETED_STATUSES);

        $inProgress = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return $inProgress->load(['answers', 'readingTask']);
        }

        $existing = (clone $baseQuery)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existing) {
            if (DualAttemptService::shouldResetPracticeAttempt($existing, $attemptNumber, self::COMPLETED_STATUSES)) {
                return $this->resetPracticeSubmission($existing);
            }

            return $existing->load(['answers', 'readingTask']);
        }

        $submission = ReadingSubmission::create([
            'reading_task_id' => $task->id,
            'student_id' => $studentId,
            'assignment_id' => empty($assignmentId) ? null : $assignmentId,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->initializeAnswersFromTask($submission);

        if ($assignmentId) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                ->where('student_id', $studentId)
                ->first();

            if ($studentAssignment) {
                $updateData = [
                    'last_activity_at' => now(),
                    'attempt_number' => $attemptNumber,
                    'attempt_count' => max((int) $studentAssignment->attempt_count, $attemptNumber),
                    'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                ];

                if (!$studentAssignment->started_at) {
                    $updateData['started_at'] = now();
                }

                if ($attemptNumber === DualAttemptService::PRACTICE_ATTEMPT) {
                    $updateData['score'] = 0;
                    $updateData['completed_at'] = null;
                }

                $studentAssignment->update($updateData);
            }
        }

        return $submission->load(['answers', 'readingTask']);
    }

    public function resetPracticeSubmission(ReadingSubmission $submission): ReadingSubmission
    {
        $submission->answers()->delete();
        $submission->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'submitted_at' => null,
            'time_taken_seconds' => null,
            'total_score' => null,
            'percentage' => null,
            'total_correct' => 0,
            'total_incorrect' => 0,
            'total_unanswered' => 0,
        ]);

        if ($submission->reading_task_id) {
            $this->initializeAnswersFromTask($submission);
        } elseif ($submission->test_id) {
            $this->initializeAnswersFromTest($submission);
        }

        return $submission->fresh(['answers', 'readingTask', 'test']);
    }

    /**
     * Get test details formatted for student
     */
    public function getTestForStudent(Test $test, string $studentId): Test
    {
        $baseQuery = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId);

        $existingSubmission = DualAttemptService::getStudentDisplaySubmission(
            $baseQuery,
            self::COMPLETED_STATUSES,
            'in_progress',
        );

        if ($existingSubmission) {
            $existingSubmission->load('answers');
        }

        $test->load([
            'passages.questionGroups.testQuestions.questionOptions',
            'passages.questionGroups.testQuestions.highlightSegments'
        ]);

        $test->existing_submission = $existingSubmission;
        $test->can_attempt = $this->canStudentAttempt($test, $studentId);
        $test->next_attempt_number = $existingSubmission
            ? DualAttemptService::resolveAttemptNumber(clone $baseQuery, self::COMPLETED_STATUSES)
            : DualAttemptService::OFFICIAL_ATTEMPT;

        return $test;
    }

    /**
     * Get task details formatted for student
     */
    public function getTaskForStudent(ReadingTask $task, string $studentId): ReadingTask
    {
        $baseQuery = ReadingSubmission::where('reading_task_id', $task->id)
            ->where('student_id', $studentId);

        $existingSubmission = DualAttemptService::getStudentDisplaySubmission(
            $baseQuery,
            self::COMPLETED_STATUSES,
            'in_progress',
        );

        if ($existingSubmission) {
            $existingSubmission->load('answers');
        }

        $task->existing_submission = $existingSubmission;
        $task->can_attempt = $this->canStudentAttemptTask($task, $studentId);
        $task->next_attempt_number = $existingSubmission
            ? DualAttemptService::resolveAttemptNumber(clone $baseQuery, self::COMPLETED_STATUSES)
            : DualAttemptService::OFFICIAL_ATTEMPT;

        return $task;
    }

    /**
     * Get submission with detailed data
     */
    public function getSubmissionWithDetails(ReadingSubmission $submission): ReadingSubmission
    {
        $submission->load([
            'answers.question',
            'vocabularyDiscoveries.vocabulary'
        ]);

        if ($submission->reading_task_id) {
            $submission->load('readingTask');
        } else {
            $submission->load([
                'test.passages.questionGroups.questions.options',
                'test.passages.questionGroups.questions.breakdowns.highlightSegments',
            ]);
        }

        return $submission;
    }

    /**
     * Initialize answer records from Legacy Test (public alias)
     */
    public function initializeAnswersFromTestPublic(ReadingSubmission $submission): void
    {
        $this->initializeAnswersFromTest($submission);
    }

    /**
     * Initialize answer records from ReadingTask JSON (public alias for assignment API)
     */
    public function initializeAnswersFromTaskPublic(ReadingSubmission $submission): void
    {
        $this->initializeAnswersFromTask($submission);
    }

    /**
     * Initialize answer records from Legacy Test
     */
    private function initializeAnswersFromTest(ReadingSubmission $submission): void
    {
        $questions = TestQuestion::whereHas('questionGroup.passage', function ($query) use ($submission) {
            $query->where('test_id', $submission->test_id);
        })->get();

        foreach ($questions as $question) {
            $submission->answers()->create([
                'question_id' => $question->id,
                'student_answer' => null,
                'correct_answer' => $question->correct_answers,
                'is_correct' => null,
                'points_earned' => 0,
            ]);
        }
    }

    /**
     * Initialize answer records from New ReadingTask (JSON passages)
     */
    private function initializeAnswersFromTask(ReadingSubmission $submission): void
    {
        $task = $submission->readingTask;
        if (!$task || !$task->passages) return;

        foreach ($task->passages as $passage) {
            foreach ($passage['question_groups'] ?? [] as $group) {
                foreach ($group['questions'] ?? [] as $question) {
                    $items = $question['items'] ?? null;

                    // For note_completion questions, expand each blank into a separate answer record
                    // so each blank gets its own point (like paragraph_completion items).
                    if (($question['question_type'] ?? '') === 'note_completion') {
                        $correctAnswers = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                        $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');
                        if (is_array($correctAnswers) && count($correctAnswers) > 0) {
                            foreach ($correctAnswers as $blank) {
                                $blankKey = $blank['option_key'] ?? null;
                                if ($blankKey === null) continue;
                                $answerId = $parentKey !== '' ? "{$parentKey}-blank-{$blankKey}" : "blank-{$blankKey}";
                                $submission->answers()->create([
                                    'reading_task_question_id' => $answerId,
                                    'question_id' => null,
                                    'student_answer' => null,
                                    'correct_answer' => $blank['option_text'] ?? '',
                                    'is_correct' => null,
                                    'points_earned' => 0,
                                ]);
                            }
                            continue;
                        }
                    }
                    // Table completion: one answer row per cell blank (same id pattern as student UI).
                    if (($question['question_type'] ?? '') === 'table_completion') {
                        $correctAnswers = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                        $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');
                        if (is_array($correctAnswers) && count($correctAnswers) > 0 && $parentKey !== '') {
                            foreach ($correctAnswers as $blank) {
                                $cellKey = $blank['option_key'] ?? null;
                                if ($cellKey === null || (string) $cellKey === '') {
                                    continue;
                                }
                                $answerId = "{$parentKey}-blank-{$cellKey}";
                                $submission->answers()->create([
                                    'reading_task_question_id' => $answerId,
                                    'question_id' => null,
                                    'student_answer' => null,
                                    'correct_answer' => $blank['option_text'] ?? '',
                                    'is_correct' => null,
                                    'points_earned' => 0,
                                ]);
                            }
                            continue;
                        }
                    }
                    // For matching_* questions that have items (e.g. matching_heading),
                    // treat each item as a separate graded question so scoring/counts match the UI.
                    if (is_array($items) && count($items) > 0) {
                        $parentKey = $question['id'] ?? $question['question_number'] ?? null;
                        foreach ($items as $idx => $item) {
                            $itemNum = $item['question_number'] ?? ($idx + 1);
                            $itemKey = $item['id']
                                ?? ($parentKey !== null ? ((string) $parentKey . '-item-' . (string) $itemNum) : (string) $itemNum);
                            $submission->answers()->create([
                                'reading_task_question_id' => $itemKey,
                                'question_id' => null,
                                'student_answer' => null,
                                'correct_answer' => $item['correct_answers']
                                    ?? $item['correct_answer']
                                    ?? [],
                                'is_correct' => null,
                                'points_earned' => 0,
                            ]);
                        }
                        continue;
                    }

                    $submission->answers()->create([
                        'reading_task_question_id' => $question['id'] ?? $question['question_number'] ?? null,
                        'question_id' => null,
                        'student_answer' => null,
                        'correct_answer' => $question['correct_answers']
                            ?? $question['correct_answer']
                            ?? [],
                        'is_correct' => null,
                        'points_earned' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Mark submission as completed
     */
    public function completeSubmission(ReadingSubmission $submission): ReadingSubmission
    {
        if ($submission->status === 'completed') {
            return $submission;
        }

        return DB::transaction(function () use ($submission) {
            $submission->update([
                'status' => 'completed',
                'submitted_at' => now(),
                'time_taken_seconds' => $submission->time_taken_seconds ?: ($submission->started_at ? now()->diffInSeconds($submission->started_at) : 0),
            ]);

            $submission->calculateScore();
            $submission->refresh(); // Critically refresh to get calculated percentage into memory

            // Sync with StudentAssignment
            $assignmentId = $submission->assignment_id;
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $submission->student_id)
                    ->first();

                if ($studentAssignment) {
                    $attemptNo = (int) ($submission->attempt_number ?? 0);
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'score' => $submission->percentage ?? 0,
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                        'attempt_count' => max((int) $studentAssignment->attempt_count, $attemptNo),
                        'attempt_number' => $attemptNo > 0 ? $attemptNo : $studentAssignment->attempt_number,
                    ]);
                }
            }

            return $submission->load('answers');
        });
    }

    /**
     * Validate if student can attempt the test
     */
    private function validateTestAttempt(Test $test, string $studentId, int $attemptNumber): ?ReadingSubmission
    {
        if ($attemptNumber !== DualAttemptService::PRACTICE_ATTEMPT) {
            if ($attemptNumber > 1 && !$test->allow_repetition) {
                throw new Exception('This test does not allow multiple attempts');
            }

            if ($test->max_repetition_count && $attemptNumber > $test->max_repetition_count) {
                throw new Exception("Maximum number of attempts ({$test->max_repetition_count}) exceeded");
            }
        }

        $existingAttempt = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existingAttempt) {
            return $existingAttempt;
        }

        return null;
    }

    /**
     * Validate if student can attempt the task
     */
    private function validateTaskAttempt(ReadingTask $task, string $studentId, int $attemptNumber): ?ReadingSubmission
    {
        if ($attemptNumber !== DualAttemptService::PRACTICE_ATTEMPT) {
            if ($attemptNumber > 1 && !$task->allow_retake) {
                // If retakes not allowed, return latest instead of failing
                $latest = ReadingSubmission::where('reading_task_id', $task->id)
                    ->where('student_id', $studentId)
                    ->latest()
                    ->first();

                if ($latest) {
                    return $latest;
                }

                throw new \Exception('This task does not allow multiple attempts');
            }

            if ($task->max_retake_attempts && $attemptNumber > $task->max_retake_attempts) {
                // If max reached, return latest instead of failing
                $latest = ReadingSubmission::where('reading_task_id', $task->id)
                    ->where('student_id', $studentId)
                    ->latest()
                    ->first();

                if ($latest) {
                    return $latest;
                }

                throw new \Exception("Maximum number of attempts ({$task->max_retake_attempts}) exceeded");
            }
        }

        $existingAttempt = ReadingSubmission::where('reading_task_id', $task->id)
            ->where('student_id', $studentId)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existingAttempt) {
            return $existingAttempt; // Resume existing
        }

        return null;
    }

    /**
     * Check if student can attempt the test
     */
    private function canStudentAttempt(Test $test, string $studentId): bool
    {
        $baseQuery = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId);

        $first = (clone $baseQuery)
            ->where('attempt_number', DualAttemptService::OFFICIAL_ATTEMPT)
            ->first();

        if (!$first) {
            return true;
        }

        return in_array($first->status, self::COMPLETED_STATUSES, true);
    }

    /**
     * Check if student can attempt the task
     */
    private function canStudentAttemptTask(ReadingTask $task, string $studentId): bool
    {
        $baseQuery = ReadingSubmission::where('reading_task_id', $task->id)
            ->where('student_id', $studentId);

        $first = (clone $baseQuery)
            ->where('attempt_number', DualAttemptService::OFFICIAL_ATTEMPT)
            ->first();

        if (!$first) {
            return true;
        }

        return in_array($first->status, self::COMPLETED_STATUSES, true);
    }

    /**
     * Get next attempt number for student
     */
    private function getNextAttemptNumber(Test $test, string $studentId): int
    {
        $lastAttempt = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->max('attempt_number');

        return ($lastAttempt ?? 0) + 1;
    }

    /**
     * Get next attempt number for student (Task)
     */
    private function getNextTaskAttemptNumber(ReadingTask $task, string $studentId): int
    {
        $lastAttempt = ReadingSubmission::where('reading_task_id', $task->id)
            ->where('student_id', $studentId)
            ->max('attempt_number');

        return ($lastAttempt ?? 0) + 1;
    }
}
