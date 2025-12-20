<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingTaskAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'class_id' => $this->class_id,
            'assigned_by' => $this->assigned_by,
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'allow_retake' => $this->allow_retake,
            'max_attempts' => $this->max_attempts,
            'class' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'code' => $this->class->code,
                ];
            }),
            'assignedBy' => $this->whenLoaded('assignedBy', function () {
                return [
                    'id' => $this->assignedBy->id,
                    'name' => $this->assignedBy->name,
                    'email' => $this->assignedBy->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}