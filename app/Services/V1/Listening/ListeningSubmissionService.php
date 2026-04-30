<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\ListeningSubmission;
use App\Models\ListeningQuestionAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListeningSubmissionService
{
    /**
     * Start or resume a listening submission.
     */
    public function startSubmission(ListeningTask $task, User $student, array $data = []): ListeningSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;

        // 1. Check for existing "to_do" submission to resume
        $existing = ListeningSubmission::where('listening_task_id', $task->id)
            ->where('student_id', $student->id)
            ->where('status', ListeningSubmission::STATUS_TO_DO)
            ->first();

        if ($existing) {
            $updates = [];
            if (!$existing->started_at) {
                $updates['started_at'] = now();
            }
            if ($assignmentId && $existing->assignment_id !== $assignmentId) {
                $updates['assignment_id'] = $assignmentId;
            }
            
            if (!empty($updates)) {
                $existing->update($updates);
            }
            
            // Sync status to StudentAssignment
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $student->id)
                    ->first();
                if ($studentAssignment && $studentAssignment->status === \App\Models\StudentAssignment::STATUS_NOT_STARTED) {
                    $studentAssignment->update(['status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS]);
                }
            }
            
            return $existing->load(['answers', 'task', 'review']);
        }

        // 2. Check retake limits for new attempt
        // Attempt number is globally unique per task/student, so do not filter by assignment_id
        $queryAttempt = ListeningSubmission::where('listening_task_id', $task->id)
            ->where('student_id', $student->id);

        $lastSubmission = $queryAttempt->orderBy('attempt_number', 'desc')->first();

        $nextAttemptNumber = ($lastSubmission ? $lastSubmission->attempt_number : 0) + 1;

        if ($nextAttemptNumber > 1) {
            // When an assignment_id is provided, the assignment's max_attempts is the authority.
            // Skip task-level retake validation so the assignment can control attempt limits.
            if (!$assignmentId) {
                if (!$task->allowsRetakes()) {
                    if ($lastSubmission) {
                        return $lastSubmission->load(['answers', 'task', 'review']);
                    }
                    throw new \Exception("Retakes are not allowed for this task.");
                }
                if ($task->max_retake_attempts && $nextAttemptNumber > $task->max_retake_attempts) {
                    if ($lastSubmission) {
                        return $lastSubmission->load(['answers', 'task', 'review']);
                    }
                    throw new \Exception("Maximum retake attempts reached.");
                }
            } else {
                // Assignment-based: check the assignment's max_attempts
                $assignment = \App\Models\Assignment::find($assignmentId);
                $maxAttempts = $assignment?->max_attempts ?? null;
                if ($maxAttempts && $nextAttemptNumber > $maxAttempts) {
                    if ($lastSubmission) {
                        return $lastSubmission->load(['answers', 'task', 'review']);
                    }
                    throw new \Exception("Maximum assignment attempts reached.");
                }
            }
        }

        // 3. Create new "to_do" submission
        $submission = ListeningSubmission::create([
            'listening_task_id' => $task->id,
            'student_id' => $student->id,
            'assignment_id' => $assignmentId,
            'status' => ListeningSubmission::STATUS_TO_DO,
            'attempt_number' => $nextAttemptNumber,
            'started_at' => now(),
            'total_correct' => 0,
            'total_incorrect' => 0,
            'total_unanswered' => $task->questions()->count(),
            'percentage' => 0,
            'total_score' => 0,
        ]);

        // Sync with StudentAssignment
        if ($assignmentId) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                ->where('student_id', $student->id)
                ->first();
            
            if ($studentAssignment) {
                // If it's a new attempt or the status is not in_progress, update it
                $studentAssignment->update([
                    'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                    'started_at' => $studentAssignment->started_at ?? now(),
                    'last_activity_at' => now(),
                    'attempt_number' => $nextAttemptNumber,
                    'attempt_count' => max($studentAssignment->attempt_count, $nextAttemptNumber),
                ]);
            }
        }

        return $submission->load(['answers', 'task', 'review']);
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
            'canRetake' => $canRetake,
            'attemptsUsed' => $attemptCount,
            'maxAttempts' => $task->max_retake_attempts,
            'lastAttempt' => $submissions->first() ? [
                'status' => $submissions->first()->status,
                'totalScore' => $submissions->first()->total_score,
                'percentage' => $submissions->first()->percentage,
            ] : null,
        ];
    }
}