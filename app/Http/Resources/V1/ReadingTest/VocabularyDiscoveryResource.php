<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VocabularyDiscoveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'test_id' => $this->test_id,
            'vocabulary_id' => $this->vocabulary_id,
            'discovered_at' => $this->discovered_at?->format('Y-m-d H:i:s'),
            'is_saved' => $this->is_saved,

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

            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'title' => $this->test->title,
                    'difficulty' => $this->test->difficulty,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}