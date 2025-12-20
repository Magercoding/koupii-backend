<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentVocabularyBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'vocabulary_id' => $this->vocabulary_id,
            'discovered_from_test_id' => $this->discovered_from_test_id,
            'is_mastered' => $this->is_mastered,
            'practice_count' => $this->practice_count,
            'last_practiced_at' => $this->last_practiced_at?->format('Y-m-d H:i:s'),
            'mastery_level' => $this->mastery_level,

            'vocabulary' => $this->whenLoaded('vocabulary', function () {
                return [
                    'id' => $this->vocabulary->id,
                    'word' => $this->vocabulary->word,
                    'translation' => $this->vocabulary->translation,
                    'spelling' => $this->vocabulary->spelling,
                    'explanation' => $this->vocabulary->explanation,
                    'audio_file_path' => $this->vocabulary->audio_file_path,
                    'audio_url' => $this->vocabulary->audio_file_path ? asset('storage/' . $this->vocabulary->audio_file_path) : null,
                    'category' => $this->when($this->vocabulary->category, [
                        'id' => $this->vocabulary->category?->id,
                        'name' => $this->vocabulary->category?->name,
                        'color_code' => $this->vocabulary->category?->color_code,
                    ]),
                ];
            }),

            'discovered_from_test' => $this->whenLoaded('discoveredFromTest', function () {
                return [
                    'id' => $this->discoveredFromTest->id,
                    'title' => $this->discoveredFromTest->title,
                    'difficulty' => $this->discoveredFromTest->difficulty,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}