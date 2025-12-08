<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'speaking_topic_id' => $this->speaking_topic_id,
            'question_number' => $this->question_number,
            'question_text' => $this->question_text,
            'time_limit_seconds' => $this->time_limit_seconds,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}