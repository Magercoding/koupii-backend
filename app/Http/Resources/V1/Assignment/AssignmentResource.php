<?php

namespace App\Http\Resources\V1\Assignment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $task = $this->getTask();
        
        return [
            'id' => $this->resource['assignment']->id,
            'type' => $this->resource['type'],
            'task' => [
                'id' => $task?->id,
                'title' => $task?->title ?? 'N/A',
                'description' => $task?->description ?? 'N/A',
                'difficulty' => $task?->difficulty_level ?? null,
                'time_limit_seconds' => $task?->time_limit_seconds ?? null
            ],
            'class' => [
                'id' => $this->resource['assignment']->class->id ?? null,
                'name' => $this->resource['assignment']->class->name ?? 'N/A'
            ],
            'assigned_by' => [
                'id' => $this->resource['assignment']->assignedBy->id ?? null,
                'name' => $this->resource['assignment']->assignedBy->name ?? 'N/A'
            ],
            'due_date' => $this->resource['assignment']->due_date,
            'max_attempts' => $this->resource['assignment']->max_attempts,
            'instructions' => $this->resource['assignment']->instructions,
            'status' => $this->resource['assignment']->status,
            'auto_grade' => $this->resource['assignment']->auto_grade ?? true,
            'created_at' => $this->resource['assignment']->created_at,
            'updated_at' => $this->resource['assignment']->updated_at
        ];
    }

    private function getTask()
    {
        return match($this->resource['type']) {
            'writing_task' => $this->resource['assignment']->writingTask,
            'reading_task' => $this->resource['assignment']->readingTask,
            'listening_task' => $this->resource['assignment']->listeningTask,
            'speaking_task' => $this->resource['assignment']->speakingTask,
            default => null
        };
    }
}