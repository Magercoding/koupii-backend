<?php

namespace App\Http\Resources\V1\ReadingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingTaskAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $class = $this->relationLoaded('class') ? $this->class : ($this->relationLoaded('classroom') ? $this->classroom : null);

        return [
            'id' => $this->id,
            'reading_task_id' => $this->task_id,
            'classroom_id' => $this->class_id,
            'assigned_by' => $this->assigned_by,
            'due_date' => $this->due_date,
            'assigned_at' => $this->created_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'classroom' => $class ? [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->class_code ?? '',
                'student_count' => $class->students_count ?? 0,
            ] : null,
            'assigned_by_user' => $this->whenLoaded('assignedBy', function () {
                return [
                    'id' => $this->assignedBy->id,
                    'name' => $this->assignedBy->name,
                    'email' => $this->assignedBy->email,
                    'role' => $this->assignedBy->role,
                ];
            }),
            'is_overdue' => $this->due_date && $this->due_date->isPast(),
            'days_until_due' => $this->due_date ? now()->diffInDays($this->due_date, false) : null,
            'assignment_status' => $this->getAssignmentStatus(),
        ];
    }

    private function getAssignmentStatus(): string
    {
        if (!$this->due_date) {
            return 'no_deadline';
        }

        $daysUntilDue = now()->diffInDays($this->due_date, false);

        if ($daysUntilDue < 0) {
            return 'overdue';
        }

        if ($daysUntilDue <= 3) {
            return 'due_soon';
        }

        return 'active';
    }
}
