<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskQuestionResource extends JsonResource
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
            'writing_task_id' => $this->writing_task_id,
            'question_type' => $this->question_type,
            'question_type_label' => $this->getQuestionTypeLabel(),
            'question_number' => $this->question_number,
            'question_text' => $this->question_text,
            'instructions' => $this->instructions,
            'word_limit' => $this->word_limit,
            'min_word_count' => $this->min_word_count,
            'time_limit_seconds' => $this->time_limit_seconds,
            'difficulty_level' => $this->difficulty_level,
            'difficulty_label' => $this->getDifficultyLabel(),
            'points' => $this->points,
            'rubric' => $this->rubric,
            'sample_answer' => $this->sample_answer,
            'question_data' => $this->question_data,
            'is_required' => $this->is_required,
            'resources' => WritingTaskQuestionResourceResource::collection($this->whenLoaded('resources')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}