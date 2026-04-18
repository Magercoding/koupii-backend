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
        // Helper to get property from resource which might be a stdClass
        $getProp = function($prop, $default = null) {
            if ($this->resource instanceof \Illuminate\Database\Eloquent\Model) {
                return $this->{$prop} ?? $default;
            }
            return property_exists($this->resource, $prop) ? $this->resource->{$prop} : $default;
        };

        return [
            'id' => $getProp('id'),
            'title' => $getProp('title'),
            'description' => $getProp('description'),
            'type' => $getProp('type'),
            'difficulty' => $getProp('difficulty'),
            'test_type' => $getProp('test_type', 'single'),
            'timer_mode' => $getProp('timer_mode', 'none'),
            'timer_settings' => $getProp('timer_settings'),
            'allow_repetition' => $getProp('allow_repetition', false),
            'max_repetition_count' => $getProp('max_repetition_count'),
            'is_public' => $getProp('is_public', false),
            'is_published' => $getProp('is_published', false),
            'settings' => $getProp('settings'),
            'created_at' => $getProp('created_at'),
            'updated_at' => $getProp('updated_at'),
            
            // Include passages when loaded
            'passages' => $this->resource instanceof \Illuminate\Database\Eloquent\Model 
                ? PassageResource::collection($this->whenLoaded('passages'))
                : [],
            
            // Creator information
            'creator' => ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('creator'))
                ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ]
                : ($getProp('creator_id') ? ['id' => $getProp('creator_id')] : null),
            
            // Class information
            'class' => ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('class'))
                ? [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'class_code' => $this->class->class_code,
                ]
                : ($getProp('class_id') ? ['id' => $getProp('class_id')] : null),
            
            // Statistics
            'statistics' => [
                'total_passages' => $this->resource instanceof \Illuminate\Database\Eloquent\Model 
                    ? ($this->passages_count ?? $this->passages?->count())
                    : 0,
                'total_questions' => ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('passages'))
                    ? $this->passages->sum(function ($passage) {
                        return $passage->questionGroups->sum(function ($group) {
                            return $group->questions->count();
                        });
                    })
                    : 0,
            ],
        ];
    }
}