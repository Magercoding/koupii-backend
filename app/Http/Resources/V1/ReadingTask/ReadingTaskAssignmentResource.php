<?php

namespace App\Http\Resources\V1\ReadingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingTaskAssignmentResource extends JsonResource
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
            'reading_task_id' => $this->reading_task_id,
            'classroom_id' => $this->classroom_id,
            'assigned_by' => $this->assigned_by,
            'due_date' => $this->due_date,
            'assigned_at' => $this->assigned_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Related data
            'reading_task' => $this->whenLoaded('readingTask', function () {
                return [
                    'id' => $this->readingTask->id,
                    'title' => $this->readingTask->title,
                    'description' => $this->readingTask->description,
                    'difficulty' => $this->readingTask->difficulty,
                    'is_published' => $this->readingTask->is_published,
                    'total_questions' => $this->readingTask->total_questions,
                    'estimated_time' => $this->readingTask->estimated_time,
                ];
            }),

            'classroom' => $this->whenLoaded('classroom', function () {
                return [
                    'id' => $this->classroom->id,
                    'name' => $this->classroom->name,
                    'code' => $this->classroom->code,
                    'student_count' => $this->classroom->students_count ?? 0,
                ];
            }),

            'assigned_by_user' => $this->whenLoaded('assignedBy', function () {
                return [
                    'id' => $this->assignedBy->id,
                    'name' => $this->assignedBy->name,
                    'email' => $this->assignedBy->email,
                    'role' => $this->assignedBy->role,
                ];
            }),

            // Status information
            'is_overdue' => $this->due_date && $this->due_date->isPast(),
            'days_until_due' => $this->due_date ? now()->diffInDays($this->due_date, false) : null,
            'assignment_status' => $this->getAssignmentStatus(),
        ];
    }

    /**
     * Get assignment status based on due date.
     */
    private function getAssignmentStatus(): string
    {
        if (!$this->due_date) {
            return 'no_deadline';
        }

        $daysUntilDue = now()->diffInDays($this->due_date, false);

        if ($daysUntilDue < 0) {
            return 'overdue';
        } elseif ($daysUntilDue <= 3) {
            return 'due_soon';
        } else {
            return 'active';
        }
    }
}