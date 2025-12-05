<?php

namespace App\Http\Resources\V1\WritingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingPromptResource extends JsonResource
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
            'title' => $this->title,
            'prompt_text' => $this->prompt_text,
            'prompt_type' => $this->prompt_type, // essay, letter, report, etc.
            'word_limit' => $this->word_limit,
            'time_limit' => $this->time_limit,
            'instructions' => $this->instructions,
            'sample_answer' => $this->when(!$isStudent, $this->sample_answer),
            'criteria' => WritingCriteriaResource::collection($this->whenLoaded('criteria')),
        ];
    }
}
