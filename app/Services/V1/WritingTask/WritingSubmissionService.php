<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingSubmission;
use App\Services\V1\Test\DualAttemptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingSubmissionService
{
    private const COMPLETED_STATUSES = ['submitted', 'reviewed', 'done'];

    /**
     * Submit student writing.
     */
    public function submitWriting($task, array $data): WritingSubmission
    {
        return DB::transaction(function () use ($task, $data) {
            $assignmentId = $data['assignment_id'] ?? null;
            $studentId = Auth::id();
            $submission = $this->resolveActiveSubmission($task, $studentId, $assignmentId);

            $submissionData = [
                'content' => $data['content'],
                'files' => $data['files'] ?? null,
                'word_count' => $this->countWords($data['content']),
                'status' => 'submitted',
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                'submitted_at' => now(),
                'assignment_id' => $assignmentId,
            ];

            if ($submission) {
                $submission->update($submissionData);
            } else {
                $attemptNumber = DualAttemptService::resolveAttemptNumber(
                    $this->baseQuery($task->id, $studentId, $assignmentId),
                    self::COMPLETED_STATUSES,
                );

                $submission = WritingSubmission::create(array_merge($submissionData, [
                    'id' => Str::uuid(),
                    'writing_task_id' => $task->id,
                    'student_id' => $studentId,
                    'attempt_number' => $attemptNumber,
                ]));
            }

            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $studentId)
                    ->first();

                if ($studentAssignment) {
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                        'started_at' => $studentAssignment->started_at ?? now(),
                        'attempt_number' => $submission->attempt_number,
                        'attempt_count' => max($studentAssignment->attempt_count ?? 0, $submission->attempt_number),
                    ]);
                }
            }

            return $submission;
        });
    }

    /**
     * Save draft (auto-save functionality).
     */
    public function saveDraft($task, array $data): WritingSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;
        $studentId = Auth::id();
        $submission = $this->resolveActiveSubmission($task, $studentId, $assignmentId);

        if ($submission) {
            $submission->update([
                'content' => $data['content'],
                'files' => $data['files'] ?? null,
                'word_count' => $this->countWords($data['content']),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
            ]);
        } else {
            $attemptNumber = DualAttemptService::resolveAttemptNumber(
                $this->baseQuery($task->id, $studentId, $assignmentId),
                self::COMPLETED_STATUSES,
            );

            $submission = WritingSubmission::create([
                'id' => Str::uuid(),
                'writing_task_id' => $task->id,
                'student_id' => $studentId,
                'assignment_id' => $assignmentId,
                'attempt_number' => $attemptNumber,
                'status' => 'to_do',
                'content' => $data['content'],
                'files' => $data['files'] ?? null,
                'word_count' => $this->countWords($data['content']),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
            ]);
        }

        if ($assignmentId) {
            $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                ->where('student_id', $studentId)
                ->first();

            if ($studentAssignment) {
                $updateData = [
                    'last_activity_at' => now(),
                    'attempt_number' => $submission->attempt_number,
                    'attempt_count' => max($studentAssignment->attempt_count ?? 0, $submission->attempt_number),
                ];

                if ($studentAssignment->status === \App\Models\StudentAssignment::STATUS_NOT_STARTED || !$studentAssignment->started_at) {
                    $updateData['status'] = \App\Models\StudentAssignment::STATUS_IN_PROGRESS;
                    $updateData['started_at'] = $studentAssignment->started_at ?? now();
                }

                if ($submission->attempt_number === DualAttemptService::PRACTICE_ATTEMPT) {
                    $updateData['score'] = 0;
                    $updateData['completed_at'] = null;
                }

                $studentAssignment->update($updateData);
            }
        }

        return $submission;
    }

    /**
     * Create retake submission based on retake option.
     */
    public function createRetakeSubmission($task, string $retakeOption, array $data = []): WritingSubmission
    {
        return DB::transaction(function () use ($task, $retakeOption, $data) {
            $assignmentId = $data['assignment_id'] ?? null;
            $studentId = Auth::id();
            $baseQuery = $this->baseQuery($task->id, $studentId, $assignmentId);
            $attemptNumber = DualAttemptService::resolveAttemptNumber($baseQuery, self::COMPLETED_STATUSES);

            $existing = (clone $baseQuery)
                ->where('attempt_number', $attemptNumber)
                ->first();

            $displaySubmission = DualAttemptService::getStudentDisplaySubmission(
                $baseQuery,
                self::COMPLETED_STATUSES,
                'to_do',
            );

            $content = match ($retakeOption) {
                'rewrite_all' => '',
                'group_similar' => $this->generateSimilarMistakesTemplate($displaySubmission),
                'choose_any' => $this->generateChosenMistakesTemplate($displaySubmission, $data['chosen_mistakes'] ?? []),
                default => throw new \Exception('Invalid retake option'),
            };

            if ($existing) {
                if (DualAttemptService::shouldResetPracticeAttempt($existing, $attemptNumber, self::COMPLETED_STATUSES)) {
                    return $this->resetPracticeSubmission($existing, $content);
                }

                $existing->update([
                    'content' => $content,
                    'status' => 'to_do',
                    'submitted_at' => null,
                    'word_count' => $this->countWords($content),
                ]);

                return $existing;
            }

            $submission = WritingSubmission::create([
                'id' => Str::uuid(),
                'writing_task_id' => $task->id,
                'student_id' => $studentId,
                'content' => $content,
                'status' => 'to_do',
                'attempt_number' => $attemptNumber,
                'assignment_id' => $assignmentId,
            ]);

            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $studentId)
                    ->first();

                if ($studentAssignment) {
                    $studentAssignment->update([
                        'last_activity_at' => now(),
                        'attempt_number' => $attemptNumber,
                        'attempt_count' => max($studentAssignment->attempt_count, $attemptNumber),
                        'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                        'score' => 0,
                        'completed_at' => null,
                        'started_at' => $studentAssignment->started_at ?? now(),
                    ]);
                }
            }

            return $submission;
        });
    }

    private function baseQuery(string $taskId, string $studentId, ?string $assignmentId): Builder
    {
        $query = WritingSubmission::where('writing_task_id', $taskId)
            ->where('student_id', $studentId);

        if ($assignmentId) {
            $query->where('assignment_id', $assignmentId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('assignment_id')->orWhere('assignment_id', '');
            });
        }

        return $query;
    }

    private function resolveActiveSubmission($task, string $studentId, ?string $assignmentId): ?WritingSubmission
    {
        $baseQuery = $this->baseQuery($task->id, $studentId, $assignmentId);
        $attemptNumber = DualAttemptService::resolveAttemptNumber($baseQuery, self::COMPLETED_STATUSES);

        $draft = (clone $baseQuery)
            ->where('status', 'to_do')
            ->first();

        if ($draft) {
            return $draft;
        }

        $existing = (clone $baseQuery)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existing && DualAttemptService::shouldResetPracticeAttempt($existing, $attemptNumber, self::COMPLETED_STATUSES)) {
            return $this->resetPracticeSubmission($existing);
        }

        return $existing && $existing->status === 'to_do' ? $existing : null;
    }

    public function resetPracticeSubmission(WritingSubmission $submission, string $content = ''): WritingSubmission
    {
        $submission->reviews()->delete();
        $submission->latestReview()?->delete();

        $submission->update([
            'content' => $content,
            'files' => null,
            'status' => 'to_do',
            'submitted_at' => null,
            'word_count' => $this->countWords($content),
            'time_taken_seconds' => null,
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

        return $submission->fresh();
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
        // Try to decode as JSON in case of multiple questions
        $data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $total = 0;
            foreach ($data as $value) {
                if (is_string($value)) {
                    $total += str_word_count(strip_tags($value));
                }
            }
            return $total;
        }

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