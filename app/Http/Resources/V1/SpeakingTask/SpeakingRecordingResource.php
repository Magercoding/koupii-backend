<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingRecordingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'question_id' => $this->question_id,
            'audio_file_path' => $this->audio_file_path,
            'audio_url' => $this->audio_file_path ? asset('storage/' . $this->audio_file_path) : null,
            'duration_seconds' => $this->duration_seconds,
            'recording_started_at' => $this->recording_started_at?->format('Y-m-d H:i:s'),
            'recording_ended_at' => $this->recording_ended_at?->format('Y-m-d H:i:s'),
            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_number' => $this->question->question_number,
                    'question_text' => $this->question->question_text,
                    'time_limit_seconds' => $this->question->time_limit_seconds,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}