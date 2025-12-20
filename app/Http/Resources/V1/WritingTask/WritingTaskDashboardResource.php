<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // Get student's latest submission for this task
        $latestSubmission = $this->submissions
            ->where('student_id', $user->id)
            ->sortByDesc('attempt_number')
            ->first();

        return [
            'task_id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'word_limit' => $this->word_limit,
            'timer_type' => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,

            // Current status for this student
            'status' => $latestSubmission ? $latestSubmission->status : 'to_do',
            'attempt_number' => $latestSubmission ? $latestSubmission->attempt_number : 0,
            'submitted_at' => $latestSubmission?->submitted_at,

            // Score information
            'score' => optional($latestSubmission?->review)->score,
            'has_feedback' => $latestSubmission?->review !== null,

            // Retake information
            'can_retake' => $this->allow_retake &&
                $latestSubmission &&
                $latestSubmission->status === 'reviewed' &&
                (!$this->max_retake_attempts || $latestSubmission->attempt_number < $this->max_retake_attempts),
            'retake_options' => $this->when($this->allow_retake, $this->retake_options),


            'status_display' => [
                'text' => $this->getStatusDisplayText($latestSubmission),
                'color' => $this->getStatusColor($latestSubmission),
                'icon' => $this->getStatusIcon($latestSubmission),
            ],

            'due_status' => [
                'is_overdue' => $this->due_date && now()->gt($this->due_date),
                'time_remaining' => $this->due_date ? $this->due_date->diffForHumans() : null,
            ],
        ];
    }

    private function getStatusDisplayText($submission)
    {
        if (!$submission)
            return 'To Do';

        return match ($submission->status) {
            'to_do' => 'In Progress',
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'done' => 'Done',
            default => 'To Do',
        };
    }

    private function getStatusColor($submission)
    {
        if (!$submission)
            return 'gray';

        return match ($submission->status) {
            'to_do' => 'yellow',
            'submitted' => 'blue',
            'reviewed' => 'green',
            'done' => 'purple',
            default => 'gray',
        };
    }

    private function getStatusIcon($submission)
    {
        if (!$submission)
            return 'clock';

        return match ($submission->status) {
            'to_do' => 'edit',
            'submitted' => 'upload',
            'reviewed' => 'check-circle',
            'done' => 'check-double',
            default => 'clock',
        };
    }
}
