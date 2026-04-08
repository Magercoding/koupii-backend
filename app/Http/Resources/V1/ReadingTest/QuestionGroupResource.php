<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray( Request $request): array
    {
        $canSeeAnswers = $this->additional['canSeeAnswers'] ?? false;

        return [
            'instruction' => $this->instruction,
            'questions' => QuestionResource::collection($this->questions)
                ->additional(['canSeeAnswers' => $canSeeAnswers]),
        ];
    }
}
