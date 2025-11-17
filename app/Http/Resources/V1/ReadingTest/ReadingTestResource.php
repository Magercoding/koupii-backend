<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Test;  
use App\Models\Passage;
class ReadingTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'passages' => PassageResource::collection($this->passages),
        ];
    }
}
