<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingTaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'test_type' => $this->test_type,
            'difficulty' => $this->difficulty,
            'allow_repetition' => $this->allow_repetition,
            'max_repetition_count' => $this->max_repetition_count,
            'timer_type' => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,
            'is_published' => $this->is_published,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'sections' => SpeakingSectionResource::collection($this->whenLoaded('speakingSections')),
            'assignments' => SpeakingTaskAssignmentResource::collection($this->whenLoaded('speakingTaskAssignments')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
