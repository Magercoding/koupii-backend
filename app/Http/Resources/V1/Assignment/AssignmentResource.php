<?php

namespace App\Http\Resources\V1\Assignment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->resource['assignment'];
        $type = $this->resource['type'];
        $isUnified = $this->resource['unified'] ?? false;

        if ($isUnified) {
            // Handle new unified assignment system
            return [
                'id' => $assignment->id,
                'type' => $type,
                'task' => [
                    'id' => $assignment->test?->id,
                    'title' => $assignment->test?->title ?? $assignment->title,
                    'description' => $assignment->test?->description ?? $assignment->description,
                    'difficulty' => $assignment->test?->difficulty ?? null,
                    'time_limit_seconds' => null // TODO: Add if needed
                ],
                'class' => [
                    'id' => $assignment->class?->id,
                    'name' => $assignment->class?->name ?? 'N/A'
                ],
                'assigned_by' => [
                    'id' => $assignment->test?->creator_id,
                    'name' => $assignment->test?->creator?->name ?? 'System'
                ],
                'due_date' => $assignment->due_date,
                'max_attempts' => $assignment->max_attempts,
                'instructions' => $assignment->description,
                'status' => $assignment->is_published ? 'active' : 'draft',
                'auto_grade' => true,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->updated_at
            ];
        }

        // Handle legacy assignment system
        $task = $this->getTask();

        return [
            'id' => $assignment->id,
            'type' => $type,
            'task' => [
                'id' => $task?->id,
                'title' => $task?->title ?? 'N/A',
                'description' => $task?->description ?? 'N/A',
                'difficulty' => $task?->difficulty_level ?? null,
                'time_limit_seconds' => $task?->time_limit_seconds ?? null
            ],
            'class' => [
                'id' => $this->getClassRelation()?->id ?? null,
                'name' => $this->getClassRelation()?->name ?? 'N/A'
            ],
            'assigned_by' => [
                'id' => $assignment->assignedBy?->id ?? null,
                'name' => $assignment->assignedBy?->name ?? 'N/A'
            ],
            'due_date' => $assignment->due_date,
            'max_attempts' => $assignment->max_attempts,
            'instructions' => $assignment->instructions,
            'status' => $assignment->status,
            'auto_grade' => $assignment->auto_grade ?? true,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at
        ];
    }

    private function getTask()
    {
        return match ($this->resource['type']) {
            'writing_task' => $this->resource['assignment']->writingTask,
            'reading_task' => $this->resource['assignment']->readingTask,
            'listening_task' => $this->resource['assignment']->listeningTask,
            'speaking_task' => $this->resource['assignment']->speakingTask,
            default => null
        };
    }

    private function getClassRelation()
    {
        return $this->resource['assignment']->class
            ?? $this->resource['assignment']->classroom
            ?? null;
    }
}