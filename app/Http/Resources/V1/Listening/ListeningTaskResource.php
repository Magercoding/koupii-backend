<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'task_type' => $this->task_type,
            'difficulty_level' => $this->difficulty_level,
            'points' => $this->points,
            'time_limit' => $this->time_limit,
            'order' => $this->order,
            'audio_file' => $this->audio_file,
            'audio_filename' => $this->audio_filename,
            'audio_url' => $this->audio_file ? asset('storage/' . $this->audio_file) : null,
            'audio_processed' => $this->audio_processed,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'test' => $this->whenLoaded('test'),
            'questions' => ListeningQuestionResource::collection($this->whenLoaded('questions')),
            'audio_segments' => $this->whenLoaded('audioSegments'),
            
            // Computed fields
            'questions_count' => $this->when(
                $this->relationLoaded('questions'), 
                fn() => $this->questions->count()
            ),
            'total_duration' => $this->when(
                $this->relationLoaded('audioSegments'),
                fn() => $this->audioSegments->sum('duration')
            ),
            'segments_count' => $this->when(
                $this->relationLoaded('audioSegments'),
                fn() => $this->audioSegments->count()
            )
        ];
    }
}