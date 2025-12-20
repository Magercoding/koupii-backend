<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingQuestionAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'question_id' => $this->question_id,
            'student_answer' => $this->student_answer,
            'correct_answer' => $this->correct_answer,
            'is_correct' => $this->is_correct,
            'points_earned' => $this->points_earned,
            'time_spent_seconds' => $this->time_spent_seconds,

            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_type' => $this->question->question_type,
                    'question_number' => $this->question->question_number,
                    'question_text' => $this->question->question_text,
                    'question_data' => $this->question->question_data,
                    'points_value' => $this->question->points_value,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}