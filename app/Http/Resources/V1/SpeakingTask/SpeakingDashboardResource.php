<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingDashboardResource extends JsonResource
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
            
            // Test information
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

            // Submission status and information
            'submission_status' => $this->submission_status ?? 'to_do',
            'latest_submission' => $this->when(
                isset($this->latest_submission),
                function () {
                    return [
                        'id' => $this->latest_submission->id,
                        'status' => $this->latest_submission->status,
                        'attempt_number' => $this->latest_submission->attempt_number,
                        'started_at' => $this->latest_submission->started_at,
                        'submitted_at' => $this->latest_submission->submitted_at,
                        'total_time_seconds' => $this->latest_submission->total_time_seconds,
                        'total_time_formatted' => $this->latest_submission->total_time_seconds
                            ? $this->formatTime($this->latest_submission->total_time_seconds)
                            : null,
                        
                        'review' => $this->when(
                            isset($this->latest_submission->review),
                            [
                                'overall_score' => $this->latest_submission->review->overall_score ?? null,
                                'feedback' => $this->latest_submission->review->feedback ?? null,
                                'reviewed_at' => $this->latest_submission->review->reviewed_at ?? null,
                            ]
                        ),
                    ];
                }
            ),

            // Status badge information for UI
            'status_info' => $this->getStatusInfo(),

            // Time-related flags
            'is_overdue' => $this->due_date && now()->gt($this->due_date) && 
                          ($this->submission_status === 'to_do'),
            'days_remaining' => $this->due_date ? now()->diffInDays($this->due_date, false) : null,
        ];
    }

    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    }

    private function getStatusInfo(): array
    {
        $status = $this->submission_status ?? 'to_do';

        $statusMap = [
            'to_do' => [
                'label' => 'To Do',
                'color' => 'blue',
                'icon' => 'clock',
                'description' => 'Not started yet'
            ],
            'in_progress' => [
                'label' => 'In Progress',
                'color' => 'yellow',
                'icon' => 'play',
                'description' => 'Currently taking the test'
            ],
            'submitted' => [
                'label' => 'Submitted',
                'color' => 'green',
                'icon' => 'check',
                'description' => 'Waiting for teacher review'
            ],
            'reviewed' => [
                'label' => 'Reviewed',
                'color' => 'purple',
                'icon' => 'star',
                'description' => 'Completed with feedback'
            ],
        ];

        return $statusMap[$status] ?? $statusMap['to_do'];
    }
}