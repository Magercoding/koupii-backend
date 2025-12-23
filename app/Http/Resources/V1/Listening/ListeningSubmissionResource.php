<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isStudent = $user && $user->role === 'student';
        $isTeacher = $user && $user->role === 'teacher';

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'score' => $this->score,
            'submission_text' => $this->submission_text,
            'file_path' => $this->file_path,
            'file_original_name' => $this->file_original_name,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            'notes' => $this->notes,
            'time_spent_seconds' => $this->time_spent_seconds,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // File download URL
            'file_download_url' => $this->when(
                $this->file_path && ($isTeacher || $this->student_id === $user?->id || $user?->role === 'admin'),
                function () {
                    return route('api.v1.listening.submission.download', $this->id);
                }
            ),

            // Task information
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'description' => $this->task->description,
                    'difficulty_level' => $this->task->difficulty_level,
                    'timer_type' => $this->task->timer_type,
                    'time_limit_seconds' => $this->task->time_limit_seconds,
                    'creator_name' => optional($this->task->creator)->name,
                ];
            }),

            // Student information (visible to teachers/admins)
            'student' => $this->when($isTeacher || $user?->role === 'admin', function () {
                return $this->whenLoaded('student', function () {
                    return [
                        'id' => $this->student->id,
                        'name' => $this->student->name,
                        'email' => $this->student->email,
                    ];
                });
            }),

            // Review information
            'review' => $this->whenLoaded('review', function () use ($isTeacher, $user) {
                return [
                    'id' => $this->review->id,
                    'score' => $this->review->score,
                    'feedback' => $this->review->feedback,
                    'private_notes' => $this->when($isTeacher || $user?->role === 'admin', $this->review->private_notes),
                    'status' => $this->review->status,
                    'reviewer_name' => optional($this->review->reviewer)->name,
                    'reviewed_at' => $this->review->reviewed_at,
                ];
            }),

            // Answer details (for review purposes)
            'answers' => $this->when($isTeacher || $user?->role === 'admin' || $this->student_id === $user?->id, function () use ($isTeacher, $user) {
                return $this->whenLoaded('answers', function () {
                    return $this->answers->map(function ($answer) {
                        return [
                            'id' => $answer->id,
                            'question_id' => $answer->question_id,
                            'answer' => $answer->answer,
                            'is_correct' => $answer->is_correct,
                            'points_awarded' => $answer->points_awarded,
                        ];
                    });
                });
            }),

            // Computed fields
            'has_review' => $this->review !== null,
            'is_graded' => $this->score !== null,
            'can_retake' => $this->when($isStudent, function () use ($isStudent) {
                return $this->task && $this->task->retakes_allowed && 
                       (!$this->task->max_retakes || $this->attempt_number < $this->task->max_retakes);
            }),
            'time_spent_formatted' => $this->when($this->time_spent_seconds, function () {
                $minutes = floor($this->time_spent_seconds / 60);
                $seconds = $this->time_spent_seconds % 60;
                return sprintf('%d:%02d', $minutes, $seconds);
            }),
        ];
    }
}