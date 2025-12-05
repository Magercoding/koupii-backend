<?php

namespace App\Http\Resources\V1\WritingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isStudent = optional($request->user())->role === 'student';

        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'creator_name' => optional($this->creator)->name,
            'test_type' => $this->test_type,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'title' => $this->title,
            'description' => $this->description,
            'timer_mode' => $this->timer_mode,
            'timer_settings' => $this->timer_settings,
            'allow_repetition' => $this->allow_repetition,
            'max_repetition_count' => $this->max_repetition_count,
            'is_public' => $this->is_public,
            'is_published' => $this->is_published,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'writing_prompts' => WritingPromptResource::collection($this->whenLoaded('writingPrompts')),
        ];
    }
}
