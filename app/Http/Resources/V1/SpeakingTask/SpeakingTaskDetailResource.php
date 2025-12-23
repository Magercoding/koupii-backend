<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingTaskDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'due_date' => $this->due_date,
            'assigned_at' => $this->assigned_at,
            'allow_retake' => $this->allow_retake,
            'max_attempts' => $this->max_attempts,
            
            // Test complete information
            'test' => [
                'id' => $this->test->id,
                'title' => $this->test->title,
                'description' => $this->test->description,
                'instructions' => $this->test->instructions,
                'difficulty' => $this->test->difficulty,
                'timer_type' => $this->test->timer_type,
                'time_limit_seconds' => $this->test->time_limit_seconds,
                'time_limit_formatted' => $this->test->time_limit_seconds 
                    ? $this->formatTime($this->test->time_limit_seconds)
                    : null,
                'sections' => $this->test->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'instructions' => $section->instructions,
                        'order_index' => $section->order_index,
                        'time_limit_seconds' => $section->time_limit_seconds,
                        'questions' => $section->questions->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'topic' => $question->topic,
                                'prompt' => $question->prompt,
                                'preparation_time_seconds' => $question->preparation_time_seconds,
                                'response_time_seconds' => $question->response_time_seconds,
                                'order_index' => $question->order_index,
                            ];
                        }),
                    ];
                }),
            ],

            // Class information
            'class' => [
                'id' => $this->class->id,
                'name' => $this->class->name,
            ],

            // Teacher information
            'assigned_by' => [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
            ],

            // Student's submission history
            'submissions' => $this->submissions->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'attempt_number' => $submission->attempt_number,
                    'started_at' => $submission->started_at,
                    'submitted_at' => $submission->submitted_at,
                    'total_time_seconds' => $submission->total_time_seconds,
                    'total_time_formatted' => $submission->total_time_seconds
                        ? $this->formatTime($submission->total_time_seconds)
                        : null,
                    
                    'review' => $this->when(
                        isset($submission->review),
                        [
                            'overall_score' => $submission->review->overall_score ?? null,
                            'pronunciation_score' => $submission->review->pronunciation_score ?? null,
                            'fluency_score' => $submission->review->fluency_score ?? null,
                            'grammar_score' => $submission->review->grammar_score ?? null,
                            'vocabulary_score' => $submission->review->vocabulary_score ?? null,
                            'content_score' => $submission->review->content_score ?? null,
                            'feedback' => $submission->review->feedback ?? null,
                            'detailed_comments' => $submission->review->detailed_comments ?? null,
                            'reviewed_at' => $submission->review->reviewed_at ?? null,
                            'reviewed_by' => $submission->review->reviewedBy 
                                ? [
                                    'id' => $submission->review->reviewedBy->id,
                                    'name' => $submission->review->reviewedBy->name,
                                ]
                                : null,
                        ]
                    ),
                    
                    'recordings' => $submission->recordings->map(function ($recording) {
                        return [
                            'id' => $recording->id,
                            'question_id' => $recording->question_id,
                            'file_path' => $recording->file_path,
                            'file_name' => $recording->file_name,
                            'duration_seconds' => $recording->duration_seconds,
                            'file_size' => $recording->file_size,
                            'transcript' => $recording->transcript,
                            'confidence_score' => $recording->confidence_score,
                            'fluency_score' => $recording->fluency_score,
                            'speaking_rate' => $recording->speaking_rate,
                            'pause_analysis' => $recording->pause_analysis,
                        ];
                    }),
                ];
            }),

            // Current submission info (if in progress)
            'current_submission' => $this->when(
                $this->hasSubmissionInProgress(),
                function () {
                    $submission = $this->submissions->where('status', 'in_progress')->first();
                    return $submission ? [
                        'id' => $submission->id,
                        'started_at' => $submission->started_at,
                        'current_question_index' => $this->getCurrentQuestionIndex($submission),
                        'completed_recordings' => $submission->recordings->count(),
                        'total_questions' => $this->getTotalQuestions(),
                    ] : null;
                }
            ),

            // Permission flags
            'can_start' => $this->canStart(),
            'can_retake' => $this->canRetake(),
            'attempts_remaining' => $this->getRemainingAttempts(),

            // Status information
            'submission_status' => $this->getSubmissionStatus(),
            'is_overdue' => $this->isOverdue(),
            'days_remaining' => $this->due_date ? now()->diffInDays($this->due_date, false) : null,
        ];
    }

    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }
    }

    private function hasSubmissionInProgress(): bool
    {
        return $this->submissions->where('status', 'in_progress')->isNotEmpty();
    }

    private function getCurrentQuestionIndex($submission): int
    {
        return $submission->recordings->count();
    }

    private function getTotalQuestions(): int
    {
        return $this->test->sections->sum(function ($section) {
            return $section->questions->count();
        });
    }

    private function canStart(): bool
    {
        // Can start if no submissions or if retakes are allowed and under max attempts
        if ($this->submissions->isEmpty()) {
            return true;
        }

        if ($this->hasSubmissionInProgress()) {
            return true; // Can continue existing submission
        }

        return $this->canRetake();
    }

    private function canRetake(): bool
    {
        if (!$this->allow_retake) {
            return false;
        }

        $completedSubmissions = $this->submissions->whereIn('status', ['submitted', 'reviewed'])->count();
        return $completedSubmissions < $this->max_attempts;
    }

    private function getRemainingAttempts(): int
    {
        if (!$this->allow_retake) {
            return $this->submissions->isEmpty() ? 1 : 0;
        }

        $completedSubmissions = $this->submissions->whereIn('status', ['submitted', 'reviewed'])->count();
        return max(0, $this->max_attempts - $completedSubmissions);
    }

    private function getSubmissionStatus(): string
    {
        if ($this->hasSubmissionInProgress()) {
            return 'in_progress';
        }

        $latestSubmission = $this->submissions->whereIn('status', ['submitted', 'reviewed'])
            ->sortByDesc('submitted_at')
            ->first();

        if (!$latestSubmission) {
            return 'to_do';
        }

        return $latestSubmission->status;
    }

    private function isOverdue(): bool
    {
        return $this->due_date && 
               now()->gt($this->due_date) && 
               $this->getSubmissionStatus() === 'to_do';
    }
}