<?php

namespace App\Http\Resources\V1\Test;

use App\Http\Resources\V1\ReadingTest\PassageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'test_type' => $this->test_type ?? $this->type,
            'timer_mode' => $this->timer_mode ?? null,
            'timer_settings' => $this->timer_settings ?? null,
            'allow_repetition' => $this->allow_repetition ?? false,
            'max_repetition_count' => $this->max_repetition_count ?? null,
            'is_public' => $this->is_public ?? false,
            'is_published' => $this->is_published,
            'settings' => $this->settings ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Include passages when loaded
            'passages' => PassageResource::collection($this->whenLoaded('passages')),
            
            // Creator information
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            
            // Class information
            'class' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'class_code' => $this->class->class_code,
                ];
            }),
            
            // Statistics
            'statistics' => [
                'total_passages' => $this->passages_count ?? null,
            ],
        ];
    }
}