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
            'meaning' => $this->meaning,

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

            'is_bookmarked' => $this->whenLoaded('bookmarks', function () {
                return $this->bookmarks->first()->is_bookmarked ?? false;
            }, false), 
        ];
    }
}
