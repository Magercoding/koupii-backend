<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'writing_task_id' => $this->writing_task_id,
            'classroom_id' => $this->classroom_id,
            'classroom_name' => optional($this->classroom)->name,
            'assigned_by' => $this->assigned_by,
            'assigned_by_name' => optional($this->assignedBy)->name,
            'assigned_at' => $this->assigned_at,
            'created_at' => $this->created_at,

           
            'classroom' => $this->when($this->relationLoaded('classroom'), function () {
                return [
                    'id' => $this->classroom->id,
                    'name' => $this->classroom->name,
                    'students_count' => $this->classroom->students_count ?? $this->classroom->students->count(),
                    'code' => $this->classroom->code,
                ];
            }),

           
            'statistics' => $this->when($this->relationLoaded('writingTask'), function () {
                $task = $this->writingTask;
                $classroomStudents = $this->classroom->students ?? collect();
                $submissions = $task->submissions->whereIn('student_id', $classroomStudents->pluck('id'));

                return [
                    'total_students' => $classroomStudents->count(),
                    'submitted_count' => $submissions->where('status', 'submitted')->count(),
                    'reviewed_count' => $submissions->where('status', 'reviewed')->count(),
                    'pending_count' => $classroomStudents->count() - $submissions->where('status', '!=', 'to_do')->count(),
                ];
            }),

         
            'assigned_time_ago' => $this->assigned_at?->diffForHumans(),
        ];
    }
}
