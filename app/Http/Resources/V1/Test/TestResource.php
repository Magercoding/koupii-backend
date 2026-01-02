<?php

namespace App\Http\Resources\V1\Test;

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
            'test_type' => $this->test_type,
            'timer_mode' => $this->timer_mode,
            'timer_settings' => $this->timer_settings,
            'allow_repetition' => $this->allow_repetition,
            'max_repetition_count' => $this->max_repetition_count,
            'is_public' => $this->is_public,
            'is_published' => $this->is_published,
            'settings' => $this->settings,
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
            
            // Statistics
            'statistics' => [
                'total_passages' => $this->passages_count ?? $this->passages?->count(),
                'total_questions' => $this->whenLoaded('passages', function () {
                    return $this->passages->sum(function ($passage) {
                        return $passage->questionGroups->sum(function ($group) {
                            return $group->questions->count();
                        });
                    });
                }),
            ],
        ];
    }
}