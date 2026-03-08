<?php

namespace App\Http\Resources\V1\Assignment;

use App\Models\Assignment;
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
            return $this->formatUnifiedAssignment($assignment, $type);
        }

        return $this->formatLegacyAssignment($assignment, $type);
    }

    private function formatUnifiedAssignment($assignment, string $type): array
    {
        
        $source = null;
        $sourceType = 'unknown';

        if ($assignment->isTestBased()) {
            $source = $assignment->test;
            $sourceType = 'test';
        } elseif ($assignment->isTaskBased()) {
            $source = $assignment->getTask();
            $sourceType = 'task';
        }

        return [
            'id' => $assignment->id,
            'type' => $type,
            'source_type' => $sourceType,
            'source' => [
                'id' => $source?->id,
                'title' => $source?->title ?? $assignment->title,
                'description' => $source?->description ?? $assignment->description,
                'difficulty' => $source?->difficulty ?? $source?->difficulty_level ?? null,
            ],
            'class' => [
                'id' => $assignment->class?->id,
                'name' => $assignment->class?->name ?? 'N/A',
            ],
            'assigned_by' => [
                'id' => $assignment->assignedBy?->id ?? $source?->creator_id ?? null,
                'name' => $assignment->assignedBy?->name ?? $source?->creator?->name ?? 'System',
            ],
            'title' => $assignment->title,
            'due_date' => $assignment->due_date,
            'max_attempts' => $assignment->max_attempts,
            'instructions' => $assignment->instructions ?? $assignment->description,
            'status' => $assignment->status ?? ($assignment->is_published ? 'active' : 'draft'),
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];
    }

    private function formatLegacyAssignment($assignment, string $type): array
    {
        $task = $this->getLegacyTask($assignment, $type);
        $class = $assignment->class ?? $assignment->classroom ?? null;

        return [
            'id' => $assignment->id,
            'type' => $type,
            'source_type' => 'task',
            'source' => [
                'id' => $task?->id,
                'title' => $task?->title ?? 'N/A',
                'description' => $task?->description ?? 'N/A',
                'difficulty' => $task?->difficulty_level ?? null,
            ],
            'class' => [
                'id' => $class?->id ?? null,
                'name' => $class?->name ?? 'N/A',
            ],
            'assigned_by' => [
                'id' => $assignment->assignedBy?->id ?? null,
                'name' => $assignment->assignedBy?->name ?? 'N/A',
            ],
            'title' => $task?->title ?? 'N/A',
            'due_date' => $assignment->due_date,
            'max_attempts' => $assignment->max_attempts ?? null,
            'instructions' => $assignment->instructions ?? null,
            'status' => $assignment->status ?? 'active',
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];
    }

    private function getLegacyTask($assignment, string $type)
    {
        return match ($type) {
            'writing_task' => $assignment->writingTask ?? null,
            'reading_task' => $assignment->readingTask ?? null,
            'listening_task' => $assignment->listeningTask ?? null,
            'speaking_task' => $assignment->speakingTask ?? null,
            default => null,
        };
    }
}