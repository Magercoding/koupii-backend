<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingSubmissionService
{
    /**
     * Submit student writing.
     */
    public function submitWriting(WritingTask $task, array $data): WritingSubmission
    {
        return DB::transaction(function () use ($task, $data) {
            // Check if student already has a non-submitted submission for this task
            $existingSubmission = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->where('status', 'to_do')
                ->first();

            if ($existingSubmission) {
                // Update existing draft to submitted
                $existingSubmission->update([
                    'content' => $data['content'],
                    'files' => $data['files'] ?? null,
                    'word_count' => $this->countWords($data['content']),
                    'status' => 'submitted',
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'submitted_at' => now(),
                ]);

                return $existingSubmission;
            } else {
                // Create new submission
                $attemptNumber = WritingSubmission::where('writing_task_id', $task->id)
                    ->where('student_id', Auth::id())
                    ->max('attempt_number') + 1;

                return WritingSubmission::create([
                    'id' => Str::uuid(),
                    'writing_task_id' => $task->id,
                    'student_id' => Auth::id(),
                    'content' => $data['content'],
                    'files' => $data['files'] ?? null,
                    'word_count' => $this->countWords($data['content']),
                    'status' => 'submitted',
                    'attempt_number' => $attemptNumber,
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'submitted_at' => now(),
                ]);
            }
        });
    }

    /**
     * Save draft (auto-save functionality).
     */
    public function saveDraft(WritingTask $task, array $data): WritingSubmission
    {
        $submission = WritingSubmission::updateOrCreate(
            [
                'writing_task_id' => $task->id,
                'student_id' => Auth::id(),
                'status' => 'to_do'
            ],
            [
                'id' => Str::uuid(),
                'content' => $data['content'],
                'files' => $data['files'] ?? null,
                'word_count' => $this->countWords($data['content']),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
            ]
        );

        return $submission;
    }

    /**
     * Create retake submission based on retake option.
     */
    public function createRetakeSubmission(WritingTask $task, string $retakeOption, array $data = []): WritingSubmission
    {
        return DB::transaction(function () use ($task, $retakeOption, $data) {
            $attemptNumber = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->max('attempt_number') + 1;

            // Check if retakes are allowed
            if (
                !$task->allow_retake ||
                ($task->max_retake_attempts && $attemptNumber > $task->max_retake_attempts)
            ) {
                throw new \Exception('Retakes not allowed or maximum attempts exceeded');
            }

            $previousSubmission = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->orderBy('attempt_number', 'desc')
                ->first();

            $content = '';

            switch ($retakeOption) {
                case 'rewrite_all':
                    $content = ''; // Start fresh
                    break;

                case 'group_similar':
                    $content = $this->generateSimilarMistakesTemplate($previousSubmission);
                    break;

                case 'choose_any':
                    $content = $this->generateChosenMistakesTemplate($previousSubmission, $data['chosen_mistakes'] ?? []);
                    break;

                default:
                    throw new \Exception('Invalid retake option');
            }

            return WritingSubmission::create([
                'id' => Str::uuid(),
                'writing_task_id' => $task->id,
                'student_id' => Auth::id(),
                'content' => $content,
                'status' => 'to_do',
                'attempt_number' => $attemptNumber,
            ]);
        });
    }

    /**
     * Mark submission as done (student acknowledges review).
     */
    public function markAsDone(WritingSubmission $submission): WritingSubmission
    {
        $submission->update(['status' => 'done']);
        return $submission;
    }

    /**
     * Count words in text content.
     */
    private function countWords(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    /**
     * Generate template for similar mistakes retake.
     */
    private function generateSimilarMistakesTemplate(?WritingSubmission $submission): string
    {
        if (!$submission) {
            return '';
        }
        // TODO: Implement AI-powered mistake grouping
        return $submission->content ?? '';
    }

    /**
     * Generate template for chosen mistakes retake.
     */
    private function generateChosenMistakesTemplate(?WritingSubmission $submission, array $chosenMistakes = []): string
    {
        if (!$submission) {
            return '';
        }
        // TODO: Implement mistake highlighting based on chosen mistakes
        return $submission->content ?? '';
    }
}