<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSeeAnswers = $this->additional['canSeeAnswers'] ?? false;
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'question_groups' => QuestionGroupResource::collection($this->questionGroups)
                ->additional(['canSeeAnswers' => $canSeeAnswers]),
        ];
    }
}
