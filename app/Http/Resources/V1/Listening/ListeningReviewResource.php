<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isTeacher = $user && $user->role === 'teacher';

        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'reviewer_id' => $this->reviewer_id,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'private_notes' => $this->when($isTeacher || $user?->role === 'admin', $this->private_notes),
            'status' => $this->status,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Submission information
            'submission' => $this->whenLoaded('submission', function () {
                return [
                    'id' => $this->submission->id,
                    'status' => $this->submission->status,
                    'submitted_at' => $this->submission->submitted_at,
                    'student_name' => optional($this->submission->student)->name,
                ];
            }),

            // Reviewer information
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'email' => $this->reviewer->email,
                ];
            }),

            // Task information through submission
            'task' => $this->when($this->relationLoaded('submission') && $this->submission->relationLoaded('task'), function () {
                return [
                    'id' => $this->submission->task->id,
                    'title' => $this->submission->task->title,
                    'difficulty_level' => $this->submission->task->difficulty_level,
                ];
            }),
        ];
    }
}