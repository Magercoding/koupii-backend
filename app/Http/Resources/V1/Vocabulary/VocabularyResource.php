<?php

namespace App\Http\Resources\V1\Vocabulary;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VocabularyResource extends JsonResource
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
            'word' => $this->word,
            'translation' => $this->translation,
            'spelling' => $this->spelling,
            'explanation' => $this->explanation,
            'audio_file_path' => $this->audio_file_path,
            'is_public' => $this->is_public,

            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'color_code' => $this->category->color_code,
                ];
            }),

            'is_bookmarked' => $this->when(
                isset($this->is_bookmarked),
                $this->is_bookmarked ?? false
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
