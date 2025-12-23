<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningTaskAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'teacher_id' => $this->teacher_id,
            'classroom_id' => $this->classroom_id,
            'assigned_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Task information
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'description' => $this->task->description,
                    'difficulty_level' => $this->task->difficulty_level,
                    'is_published' => $this->task->is_published,
                ];
            }),

            // Teacher information
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->name,
                    'email' => $this->teacher->email,
                ];
            }),

            // Classroom information
            'classroom' => $this->whenLoaded('classroom', function () {
                return [
                    'id' => $this->classroom->id,
                    'name' => $this->classroom->name,
                    'description' => $this->classroom->description,
                    'students_count' => $this->classroom->students_count ?? $this->classroom->students()->count(),
                ];
            }),
        ];
    }
}