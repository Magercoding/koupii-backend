<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
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
     * Submit student listening task.
     */
    public function submit(ListeningTask $task, User $student, array $data, Request $request): ListeningSubmission
    {
        return DB::transaction(function () use ($task, $student, $data, $request) {
            // Check if student already has a non-submitted submission for this task
            $existingSubmission = ListeningSubmission::where('listening_task_id', $task->id)
                ->where('student_id', $student->id)
                ->where('status', ListeningSubmission::STATUS_TO_DO)
                ->first();

            if ($existingSubmission) {
                // Update existing draft to submitted
                $existingSubmission->update([
                    'status' => ListeningSubmission::STATUS_SUBMITTED,
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'audio_play_counts' => $data['audio_play_counts'] ?? null,
                    'submitted_at' => now(),
                ]);

                // Save answers
                if (isset($data['answers'])) {
                    $this->saveAnswers($existingSubmission, $data['answers']);
                }

                // Calculate score
                $this->calculateScore($existingSubmission);

                return $existingSubmission->fresh();
            } else {
                // Create new submission
                $attemptNumber = ListeningSubmission::where('listening_task_id', $task->id)
                    ->where('student_id', $student->id)
                    ->max('attempt_number') + 1;

                $submission = ListeningSubmission::create([
                    'id' => Str::uuid(),
                    'listening_task_id' => $task->id,
                    'student_id' => $student->id,
                    'status' => ListeningSubmission::STATUS_SUBMITTED,
                    'attempt_number' => $attemptNumber,
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'audio_play_counts' => $data['audio_play_counts'] ?? null,
                    'submitted_at' => now(),
                    'started_at' => $data['started_at'] ?? now(),
                ]);

                // Save answers
                if (isset($data['answers'])) {
                    $this->saveAnswers($submission, $data['answers']);
                }

                // Calculate score
                $this->calculateScore($submission);

                return $submission->fresh();
            }
        });
    }

    /**
     * Save draft (auto-save functionality).
     */
    public function saveDraft(ListeningTask $task, User $student, array $data): ListeningSubmission
    {
        $submission = ListeningSubmission::updateOrCreate(
            [
                'listening_task_id' => $task->id,
                'student_id' => $student->id,
                'status' => ListeningSubmission::STATUS_TO_DO
            ],
            [
                'id' => Str::uuid(),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                'audio_play_counts' => $data['audio_play_counts'] ?? null,
                'started_at' => $data['started_at'] ?? now(),
            ]
        );

        // Save partial answers
        if (isset($data['answers'])) {
            $this->saveAnswers($submission, $data['answers']);
        }

        return $submission;
    }

    /**
     * Save answers for a submission.
     */
    private function saveAnswers(ListeningSubmission $submission, array $answers): void
    {
        // Delete existing answers for this submission
        ListeningQuestionAnswer::where('listening_submission_id', $submission->id)->delete();

        // Save new answers
        foreach ($answers as $answerData) {
            ListeningQuestionAnswer::create([
                'id' => Str::uuid(),
                'listening_submission_id' => $submission->id,
                'listening_question_id' => $answerData['question_id'],
                'answer' => $answerData['answer'] ?? null,
                'selected_option' => $answerData['selected_option'] ?? null,
                'is_correct' => $answerData['is_correct'] ?? null,
            ]);
        }
    }

    /**
     * Calculate score for a submission.
     */
    private function calculateScore(ListeningSubmission $submission): void
    {
        $answers = $submission->answers;
        $totalQuestions = $answers->count();
        
        if ($totalQuestions === 0) {
            $submission->update([
                'total_score' => 0,
                'percentage' => 0,
                'total_correct' => 0,
                'total_incorrect' => 0,
                'total_unanswered' => 0,
            ]);
            return;
        }

        $correctCount = $answers->where('is_correct', true)->count();
        $incorrectCount = $answers->where('is_correct', false)->count();
        $unansweredCount = $answers->whereNull('is_correct')->count();

        $percentage = ($correctCount / $totalQuestions) * 100;
        $totalScore = ($correctCount / $totalQuestions) * ($submission->task->points ?? 100);

        $submission->update([
            'total_score' => round($totalScore, 2),
            'percentage' => round($percentage, 2),
            'total_correct' => $correctCount,
            'total_incorrect' => $incorrectCount,
            'total_unanswered' => $unansweredCount,
        ]);
    }

    /**
     * Create retake submission.
     */
    public function createRetakeSubmission(ListeningTask $task, User $student, string $retakeOption, array $data = []): ListeningSubmission
    {
        return DB::transaction(function () use ($task, $student, $retakeOption, $data) {
            $attemptNumber = ListeningSubmission::where('listening_task_id', $task->id)
                ->where('student_id', $student->id)
                ->max('attempt_number') + 1;

            // Check if retakes are allowed
            if (!$task->allow_retake || ($task->max_retake_attempts && $attemptNumber > $task->max_retake_attempts)) {
                throw new \Exception('Retakes not allowed or maximum attempts exceeded');
            }

            $previousSubmission = ListeningSubmission::where('listening_task_id', $task->id)
                ->where('student_id', $student->id)
                ->orderBy('attempt_number', 'desc')
                ->first();

            $newSubmission = ListeningSubmission::create([
                'id' => Str::uuid(),
                'listening_task_id' => $task->id,
                'student_id' => $student->id,
                'status' => ListeningSubmission::STATUS_TO_DO,
                'attempt_number' => $attemptNumber,
                'time_taken_seconds' => null,
                'audio_play_counts' => null,
                'started_at' => now(),
            ]);

            // Handle retake options
            if ($retakeOption === 'fresh_start') {
                // Start fresh - no copied data
            } elseif ($retakeOption === 'continue_from_previous' && $previousSubmission) {
                // Copy previous answers as starting point
                $previousAnswers = $previousSubmission->answers;
                foreach ($previousAnswers as $answer) {
                    ListeningQuestionAnswer::create([
                        'id' => Str::uuid(),
                        'listening_submission_id' => $newSubmission->id,
                        'listening_question_id' => $answer->listening_question_id,
                        'answer' => $answer->answer,
                        'selected_option' => $answer->selected_option,
                        'is_correct' => null, // Reset correctness to allow re-evaluation
                    ]);
                }
            }

            return $newSubmission;
        });
    }

    /**
     * Get submission statistics for a task.
     */
    public function getSubmissionStats(ListeningTask $task): array
    {
        $submissions = ListeningSubmission::where('listening_task_id', $task->id)->get();

        return [
            'total_submissions' => $submissions->count(),
            'submitted_count' => $submissions->where('status', ListeningSubmission::STATUS_SUBMITTED)->count(),
            'reviewed_count' => $submissions->where('status', ListeningSubmission::STATUS_REVIEWED)->count(),
            'done_count' => $submissions->where('status', ListeningSubmission::STATUS_DONE)->count(),
            'average_score' => $submissions->where('total_score', '>', 0)->avg('total_score'),
            'highest_score' => $submissions->max('total_score'),
            'lowest_score' => $submissions->where('total_score', '>', 0)->min('total_score'),
            'average_time' => $submissions->whereNotNull('time_taken_seconds')->avg('time_taken_seconds'),
        ];
    }

    /**
     * Upload files for submission.
     */
    public function uploadFiles(ListeningSubmission $submission, array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            $path = Storage::putFile('listening_submissions/' . $submission->id, $file);
            $uploadedFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'type' => $file->getClientMimeType(),
            ];
        }

        $submission->update(['files' => $uploadedFiles]);

        return $uploadedFiles;
    }

    /**
     * Check if student can retake the task.
     */
    public function canRetake(ListeningTask $task, User $student): array
    {
        $submissions = ListeningSubmission::where('listening_task_id', $task->id)
            ->where('student_id', $student->id)
            ->orderBy('attempt_number', 'desc')
            ->get();

        $lastAttempt = $submissions->first();
        $attemptCount = $submissions->count();

        return [
            'can_retake' => $task->allow_retake && 
                          (!$task->max_retake_attempts || $attemptCount < $task->max_retake_attempts) &&
                          $lastAttempt && 
                          in_array($lastAttempt->status, [ListeningSubmission::STATUS_SUBMITTED, ListeningSubmission::STATUS_REVIEWED]),
            'attempts_used' => $attemptCount,
            'max_attempts' => $task->max_retake_attempts,
            'last_score' => $lastAttempt?->total_score,
            'last_percentage' => $lastAttempt?->percentage,
        ];
    }
}