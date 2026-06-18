<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\ListeningSubmission;
use App\Models\ListeningQuestionAnswer;
use App\Models\User;
use App\Services\V1\Test\DualAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListeningSubmissionService
{
    private const COMPLETED_STATUSES = ['submitted', 'reviewed', 'done', 'completed'];

    /**
     * Start or resume a listening submission.
     */
    public function startSubmission(ListeningTask $task, User $student, array $data = []): ListeningSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;
        $studentId = $student->id;

        $baseQuery = ListeningSubmission::where('listening_task_id', $task->id)
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
            ->where('status', ListeningSubmission::STATUS_TO_DO)
            ->first();

        if ($inProgress) {
            $updates = [];
            if (!$inProgress->started_at) {
                $updates['started_at'] = now();
            }
            if ($assignmentId && $inProgress->assignment_id !== $assignmentId) {
                $updates['assignment_id'] = $assignmentId;
            }

            if (!empty($updates)) {
                $inProgress->update($updates);
            }

            $this->syncStudentAssignmentInProgress($assignmentId, $studentId, (int) $inProgress->attempt_number);

            return $inProgress->load(['answers', 'task', 'review']);
        }

        $existing = (clone $baseQuery)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existing) {
            if (DualAttemptService::shouldResetPracticeAttempt($existing, $attemptNumber, self::COMPLETED_STATUSES)) {
                return $this->resetPracticeSubmission($existing, $task);
            }

            return $existing->load(['answers', 'task', 'review']);
        }

        $submission = ListeningSubmission::create([
            'listening_task_id' => $task->id,
            'student_id' => $studentId,
            'assignment_id' => empty($assignmentId) ? null : $assignmentId,
            'attempt_number' => $attemptNumber,
            'status' => ListeningSubmission::STATUS_TO_DO,
            'started_at' => now(),
            'total_correct' => 0,
            'total_incorrect' => 0,
            'total_unanswered' => $task->questions()->count(),
            'percentage' => 0,
            'total_score' => 0,
        ]);

        $this->syncStudentAssignmentInProgress($assignmentId, $studentId, $attemptNumber, $attemptNumber === DualAttemptService::PRACTICE_ATTEMPT);

        return $submission->load(['answers', 'task', 'review']);
    }

    public function resetPracticeSubmission(ListeningSubmission $submission, ?ListeningTask $task = null): ListeningSubmission
    {
        $submission->answers()->delete();

        $task ??= $submission->task;
        $questionCount = $task ? $task->questions()->count() : 0;

        $submission->update([
            'status' => ListeningSubmission::STATUS_TO_DO,
            'started_at' => now(),
            'submitted_at' => null,
            'time_taken_seconds' => null,
            'total_correct' => 0,
            'total_incorrect' => 0,
            'total_unanswered' => $questionCount,
            'percentage' => 0,
            'total_score' => 0,
            'audio_play_counts' => null,
        ]);

        if ($submission->assignment_id) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $submission->assignment_id)
                ->where('student_id', $submission->student_id)
                ->first();

            if ($studentAssignment) {
                $studentAssignment->update([
                    'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                    'score' => 0,
                    'completed_at' => null,
                    'last_activity_at' => now(),
                    'attempt_number' => DualAttemptService::PRACTICE_ATTEMPT,
                ]);
            }
        }

        return $submission->fresh(['answers', 'task', 'review']);
    }

    private function syncStudentAssignmentInProgress(
        ?string $assignmentId,
        string $studentId,
        int $attemptNumber,
        bool $resetScore = false,
    ): void {
        if (!$assignmentId) {
            return;
        }

        $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();

        if (!$studentAssignment) {
            return;
        }

        $updateData = [
            'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
            'started_at' => $studentAssignment->started_at ?? now(),
            'last_activity_at' => now(),
            'attempt_number' => $attemptNumber,
            'attempt_count' => max((int) $studentAssignment->attempt_count, $attemptNumber),
        ];

        if ($resetScore) {
            $updateData['score'] = 0;
            $updateData['completed_at'] = null;
        }

        $studentAssignment->update($updateData);
    }

    /**
     * Save/Auto-save a draft of the listening submission.
     */
    public function saveDraft(ListeningTask $task, User $student, array $data): ListeningSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;

        $query = ListeningSubmission::where('listening_task_id', $task->id)
            ->where('student_id', $student->id)
            ->where('status', ListeningSubmission::STATUS_TO_DO);

        if ($assignmentId) {
            $query->where('assignment_id', $assignmentId);
        }

        $submission = $query->first();

        if (!$submission) {
            $submission = $this->startSubmission($task, $student, $data);
        }

        $timeSpent = $data['time_spent_seconds'] ?? $data['time_taken_seconds'] ?? $submission->time_taken_seconds ?? 0;
        
        $submission->update([
            'time_taken_seconds' => $timeSpent,
            'audio_play_counts' => $data['audio_play_counts'] ?? $submission->audio_play_counts,
        ]);

        if (isset($data['answers']) && is_array($data['answers'])) {
            $this->saveAnswers($submission, $data['answers']);
        }

        return $submission;
    }

    /**
     * Submit a specific submission directly by its model instance.
     * Avoids the fragile re-query by status = 'to_do'.
     */
    public function submitById(ListeningSubmission $submission, User $student, array $data): ListeningSubmission
    {
        return DB::transaction(function () use ($submission, $student, $data) {
            $timeTaken = $data['time_taken_seconds'] ?? $data['time_spent_seconds'] ?? $submission->time_taken_seconds ?? 0;

            // Save final answers before grading
            if (isset($data['answers']) && is_array($data['answers'])) {
                $this->saveAnswers($submission, $data['answers']);
            }

            // Grade and finalize
            $this->gradeSubmission($submission);

            $submission->update([
                'status' => ListeningSubmission::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'time_taken_seconds' => $timeTaken,
                'audio_play_counts' => $data['audio_play_counts'] ?? $submission->audio_play_counts,
            ]);

            // Sync with StudentAssignment
            $assignmentId = $submission->assignment_id;
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $student->id)
                    ->first();

                if ($studentAssignment) {
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'score' => max($studentAssignment->score ?? 0, $submission->percentage),
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            return $submission->fresh(['answers', 'task', 'review']);
        });
    }

    /**
     * Finalize and submit a listening task (legacy — finds submission by status).
     */
    public function submit(ListeningTask $task, User $student, array $data, ?Request $request = null): ListeningSubmission
    {
        return DB::transaction(function () use ($task, $student, $data) {
            $assignmentId = $data['assignment_id'] ?? null;

            // Find current active effort
            $query = ListeningSubmission::where('listening_task_id', $task->id)
                ->where('student_id', $student->id)
                ->where('status', ListeningSubmission::STATUS_TO_DO);

            if ($assignmentId) {
                $query->where('assignment_id', $assignmentId);
            }

            $submission = $query->first();

            // Handle edge case where student tries to submit without starting
            if (!$submission) {
                $submission = $this->startSubmission($task, $student, $data);
            }

            $timeTaken = $data['time_taken_seconds'] ?? $data['time_spent_seconds'] ?? $submission->time_taken_seconds ?? 0;

            // Save final answers before grading
            if (isset($data['answers']) && is_array($data['answers'])) {
                $this->saveAnswers($submission, $data['answers']);
            }

            // Grade and finalize
            $this->gradeSubmission($submission);

            $submission->update([
                'status' => ListeningSubmission::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'time_taken_seconds' => $timeTaken,
                'audio_play_counts' => $data['audio_play_counts'] ?? $submission->audio_play_counts,
            ]);

            // Sync with StudentAssignment
            $assignmentId = $submission->assignment_id;
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $student->id)
                    ->first();

                if ($studentAssignment) {
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'score' => max($studentAssignment->score ?? 0, $submission->percentage),
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            return $submission->fresh(['answers', 'task', 'review']);
        });
    }

    /**
     * Save answers for a submission.
     */
    protected function saveAnswers(ListeningSubmission $submission, array $answersData): void
    {
        foreach ($answersData as $key => $answer) {
            $qId = null;
            $ansValue = null;
            $ansTime = 0;
            $ansAudio = 0;

            if (is_array($answer)) {
                $qId = $answer['question_id'] ?? $answer['questionId'] ?? null;
                $ansValue = $answer['answer'] ?? $answer['answer_text'] ?? $answer['answerText'] ?? null;
                $ansTime = $answer['time_spent_seconds'] ?? $answer['timeSpentSeconds'] ?? 0;
                $ansAudio = $answer['audio_play_count'] ?? $answer['audioPlayCount'] ?? 0;
            } else {
                // If it's a simple key-value pair, check if key looks like a UUID
                if (is_string($key) && strlen($key) > 30) {
                    $qId = $key;
                    $ansValue = $answer;
                }
            }

            // Support both array of objects from newer frontend and key-value pairs from legacy or other clients
            if (!$qId || !is_string($qId) || strlen($qId) < 30) continue;

            try {
                // Ensure the answer is saved correctly. 
                // Since 'answer' is casted as 'array' in the model, Eloquent handles common types well.
                ListeningQuestionAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'question_id' => $qId,
                    ],
                    [
                        'answer' => $ansValue,
                        'time_spent_seconds' => $ansTime,
                        'audio_play_count' => $ansAudio,
                    ]
                );
            } catch (\Exception $e) {
                \Log::error("Failed to save answer for submission {$submission->id}, question {$qId}: " . $e->getMessage());
                // Continue to next answer instead of failing the whole request
            }
        }
    }

    /**
     * Grade all answers in a submission and update totals.
     */
    protected function gradeSubmission(ListeningSubmission $submission): void
    {
        $answers = $submission->answers()->get();
        $questions = $submission->task->questions()->get()->keyBy('id');

        $totalCorrect = 0;
        $earnedPoints = 0;

        foreach ($answers as $answer) {
            $question = $questions->get($answer->question_id);
            if (!$question) continue;

            $isCorrect = $question->isCorrectAnswer($answer->answer);
            $points = $isCorrect ? $question->calculatePoints($answer->answer) : 0;

            $answer->update([
                'is_correct' => $isCorrect,
                'points_earned' => $points,
            ]);

            if ($isCorrect) {
                $totalCorrect++;
                $earnedPoints += $points;
            }
        }

        $totalQuestions = $questions->count();
        $totalIncorrect = max(0, $answers->where('is_correct', false)->count());
        $totalUnanswered = max(0, $totalQuestions - $answers->count());
        $totalPossiblePoints = $submission->task->getTotalPoints();
        $percentage = $totalPossiblePoints > 0 ? ($earnedPoints / $totalPossiblePoints) * 100 : 0;

        $submission->update([
            'total_score' => $earnedPoints,
            'percentage' => $percentage,
            'total_correct' => $totalCorrect,
            'total_incorrect' => $totalIncorrect, // Better calculation
            'total_unanswered' => $totalUnanswered,
        ]);

        // Sync to StudentAssignment (important for dashboard list view)
        if ($submission->assignment_id) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $submission->assignment_id)
                ->where('student_id', $submission->student_id)
                ->first();

            if ($studentAssignment) {
                $studentAssignment->update([
                    'score' => max($studentAssignment->score ?? 0, $percentage),
                    'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                    'completed_at' => now(),
                    'last_activity_at' => now(),
                    'attempt_number' => $submission->attempt_number,
                    'attempt_count' => max($studentAssignment->attempt_count, $submission->attempt_number),
                ]);
            }
        }
    }

    /**
     * Check if a student can retake the task.
     */
    public function canRetake(ListeningTask $task, User $student): array
    {
        $submissions = ListeningSubmission::where('listening_task_id', $task->id)
            ->where('student_id', $student->id)
            ->orderBy('attempt_number', 'desc')
            ->get();

        $attemptCount = $submissions->count();
        $canRetake = $task->allowsRetakes() && 
                    (!$task->max_retake_attempts || $attemptCount < $task->max_retake_attempts);

        return [
            'canRetake' => true,
            'attemptsUsed' => min($attemptCount, DualAttemptService::PRACTICE_ATTEMPT),
            'maxAttempts' => DualAttemptService::PRACTICE_ATTEMPT,
            'lastAttempt' => $submissions->first() ? [
                'status' => $submissions->first()->status,
                'totalScore' => $submissions->first()->total_score,
                'percentage' => $submissions->first()->percentage,
            ] : null,
        ];
    }
}