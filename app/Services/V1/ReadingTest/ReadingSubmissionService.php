<?php

namespace App\Services\V1\ReadingTest;

use App\Models\Test;
use App\Models\ReadingTask;
use App\Models\ReadingSubmission;
use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ReadingSubmissionService
{
    /**
     * Get student's reading test submissions
     */
    public function getStudentSubmissions(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return ReadingSubmission::with(['test:id,title,difficulty,type', 'readingTask:id,title,difficulty', 'answers'])
            ->where('student_id', $studentId)
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
        return DB::transaction(function () use ($test, $studentId, $data) {
            if ($test->type !== 'reading') {
                throw new Exception('Invalid test type for reading submission');
            }

            $this->validateTestAttempt($test, $studentId, $data['attempt_number'] ?? 1);

            $submission = ReadingSubmission::create([
                'test_id' => $test->id,
                'student_id' => $studentId,
                'attempt_number' => $data['attempt_number'] ?? 1,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $this->initializeAnswersFromTest($submission);

            return $submission->load('test', 'answers');
        });
    }

    /**
     * Start a new reading task attempt (New ReadingTask)
     */
    public function startReadingTask(ReadingTask $task, string $studentId, array $data): ReadingSubmission
    {
        return DB::transaction(function () use ($task, $studentId, $data) {
            $assignmentId = $data['assignment_id'] ?? null;
            $attemptNumber = $data['attempt_number'] ?? 1;

            $existing = $this->validateTaskAttempt($task, $studentId, $attemptNumber);
            if ($existing) {
                return $existing;
            }

            $submission = ReadingSubmission::create([
                'reading_task_id' => $task->id,
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $this->initializeAnswersFromTask($submission);

            // Sync with StudentAssignment
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $studentId)
                    ->first();

                if ($studentAssignment) {
                    $isNewlyStarted = $attemptNumber > $studentAssignment->attempt_count;
                    $isCompletedInSubmission = in_array($submission->status, ['completed', 'submitted']);

                    $updateData = [
                        'last_activity_at' => now(),
                        'attempt_number' => $attemptNumber,
                        'attempt_count' => max($studentAssignment->attempt_count, $attemptNumber),
                    ];

                    if (!$studentAssignment->started_at) {
                        $updateData['started_at'] = now();
                    }

                    // ONLY set to IN_PROGRESS if the underlying submission data isn't already completed
                    // This prevents showing answers in continue flow if the database was in an inconsistent state
                    if (!$isCompletedInSubmission) {
                        $updateData['status'] = \App\Models\StudentAssignment::STATUS_IN_PROGRESS;
                    }

                    // If this is truly a new attempt, clear the global score and completion date
                    if ($isNewlyStarted) {
                        $updateData['score'] = 0;
                        $updateData['completed_at'] = null;
                    }

                    $studentAssignment->update($updateData);
                }
            }

            return $submission->load('readingTask', 'answers');
        });
    }

    /**
     * Get test details formatted for student
     */
    public function getTestForStudent(Test $test, string $studentId): Test
    {
        $existingSubmission = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        $test->load([
            'passages.questionGroups.testQuestions.questionOptions',
            'passages.questionGroups.testQuestions.highlightSegments'
        ]);

        $test->existing_submission = $existingSubmission;
        $test->can_attempt = $this->canStudentAttempt($test, $studentId);
        $test->next_attempt_number = $this->getNextAttemptNumber($test, $studentId);

        return $test;
    }

    /**
     * Get task details formatted for student
     */
    public function getTaskForStudent(ReadingTask $task, string $studentId): ReadingTask
    {
        $existingSubmission = ReadingSubmission::where('reading_task_id', $task->id)
            ->where('student_id', $studentId)
            ->with('answers')
            ->latest()
            ->first();

        $task->existing_submission = $existingSubmission;
        $task->can_attempt = $this->canStudentAttemptTask($task, $studentId);
        $task->next_attempt_number = $this->getNextTaskAttemptNumber($task, $studentId);

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
                'test.passages.questionGroups.testQuestions.questionOptions',
                'test.passages.questionGroups.testQuestions.highlightSegments',
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
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'score' => $submission->percentage ?? 0,
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            return $submission->load('answers');
        });
    }

    /**
     * Validate if student can attempt the test
     */
    private function validateTestAttempt(Test $test, string $studentId, int $attemptNumber): void
    {
        if ($attemptNumber > 1 && !$test->allow_repetition) {
            throw new Exception('This test does not allow multiple attempts');
        }

        if ($test->max_repetition_count && $attemptNumber > $test->max_repetition_count) {
            throw new Exception("Maximum number of attempts ({$test->max_repetition_count}) exceeded");
        }

        $existingAttempt = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existingAttempt) {
            throw new Exception('This attempt has already been started');
        }
    }

    /**
     * Validate if student can attempt the task
     */
    private function validateTaskAttempt(ReadingTask $task, string $studentId, int $attemptNumber): ?ReadingSubmission
    {
        if ($attemptNumber > 1 && !$task->allow_retake) {
            // If retakes not allowed, return latest instead of failing
            $latest = ReadingSubmission::where('reading_task_id', $task->id)
                ->where('student_id', $studentId)
                ->latest()
                ->first();
            
            if ($latest) return $latest;
            
            throw new \Exception('This task does not allow multiple attempts');
        }

        if ($task->max_retake_attempts && $attemptNumber > $task->max_retake_attempts) {
            // If max reached, return latest instead of failing
            $latest = ReadingSubmission::where('reading_task_id', $task->id)
                ->where('student_id', $studentId)
                ->latest()
                ->first();
            
            if ($latest) return $latest;

            throw new \Exception("Maximum number of attempts ({$task->max_retake_attempts}) exceeded");
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
        if (!$test->allow_repetition) {
            return !ReadingSubmission::where('test_id', $test->id)
                ->where('student_id', $studentId)
                ->exists();
        }

        if ($test->max_repetition_count) {
            $attemptCount = ReadingSubmission::where('test_id', $test->id)
                ->where('student_id', $studentId)
                ->count();
            
            return $attemptCount < $test->max_repetition_count;
        }

        return true;
    }

    /**
     * Check if student can attempt the task
     */
    private function canStudentAttemptTask(ReadingTask $task, string $studentId): bool
    {
        if (!$task->allow_retake) {
            return !ReadingSubmission::where('reading_task_id', $task->id)
                ->where('student_id', $studentId)
                ->exists();
        }

        if ($task->max_retake_attempts) {
            $attemptCount = ReadingSubmission::where('reading_task_id', $task->id)
                ->where('student_id', $studentId)
                ->count();
            
            return $attemptCount < $task->max_retake_attempts;
        }

        return true;
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
