<?php

namespace App\Http\Resources\V1\Assignment;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->resource['assignment'];
        $type       = $this->resource['type'] ?? $assignment->type;

        $source     = null;
        $sourceType = 'unknown';

        if ($assignment->isTestBased()) {
            $source     = $assignment->test;
            $sourceType = 'test';
        } elseif ($assignment->isTaskBased()) {
            $source     = $assignment->getTask();
            $sourceType = 'task';
        }

        return [
            'id'          => $assignment->id,
            'type'        => $type,
            'source_type' => $sourceType,
            'source'      => [
                'id'          => $source?->id,
                'title'       => $source?->title ?? $assignment->title,
                'description' => $source?->description ?? $assignment->description,
                'difficulty'  => $source?->difficulty ?? $source?->difficulty_level ?? null,
            ],
            'class'       => [
                'id'   => $assignment->class?->id,
                'name' => $assignment->class?->name ?? 'N/A',
            ],
            'assigned_by' => [
                'id'   => $assignment->assignedBy?->id ?? null,
                'name' => $assignment->assignedBy?->name ?? 'System',
            ],
            'title'        => $assignment->title,
            'due_date'     => $assignment->due_date,
            'max_attempts' => $assignment->max_attempts,
            'instructions' => $assignment->instructions ?? $assignment->description,
            'status'       => $assignment->status ?? ($assignment->is_published ? 'active' : 'draft'),
            'student_progress' => $this->resource['student_progress'] ?? null,
            'created_at'   => $assignment->created_at,
            'updated_at'   => $assignment->updated_at,
        ];
    }
}
