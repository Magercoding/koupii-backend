<?php

namespace App\Http\Resources\V1\WritingTask;

use App\Http\Resources\V1\WiritingTest\WritingReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isStudent = $user && $user->role === 'student';
        $isOwnSubmission = $user && $this->student_id === $user->id;

        return [
            'id' => $this->id,
            'writing_task_id' => $this->writing_task_id,
            'student_id' => $this->student_id,
            'student_name' => optional($this->student)->name,
            'content' => $this->when($isOwnSubmission || !$isStudent, $this->content),
            'word_count' => $this->word_count,
            'status' => $this->status,
            'attempt_number' => $this->attempt_number,
            'time_taken_seconds' => $this->time_taken_seconds,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Files (if allowed)
            'files' => $this->when($this->files && ($isOwnSubmission || !$isStudent), $this->files),

            // Review information
            'review' => $this->when(
                $this->relationLoaded('review') && $this->review,
                new  WritingReviewResource($this->review)
            ),

            // Task information (when loaded)
            'task' => $this->when(
                $this->relationLoaded('writingTask'),
                new WritingTaskResource($this->writingTask)
            ),

            // Time information
            'time_formatted' => $this->when($this->time_taken_seconds, function () {
                return $this->formatTime($this->time_taken_seconds);
            }),

            // Status badges for UI
            'status_display' => [
                'text' => ucfirst(str_replace('_', ' ', $this->status)),
                'color' => $this->getStatusColor(),
                'icon' => $this->getStatusIcon(),
            ],
        ];
    }

    /**
     * Format time in seconds to human readable format.
     */
    private function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get status color for UI.
     */
    private function getStatusColor()
    {
        return match ($this->status) {
            'to_do' => 'gray',
            'submitted' => 'blue',
            'reviewed' => 'green',
            'done' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI.
     */
    private function getStatusIcon()
    {
        return match ($this->status) {
            'to_do' => 'clock',
            'submitted' => 'upload',
            'reviewed' => 'check-circle',
            'done' => 'check-double',
            default => 'question',
        };
    }
}
