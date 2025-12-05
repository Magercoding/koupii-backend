<?php

namespace App\Http\Resources\V1\WritingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingCriteriaResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'max_score' => $this->max_score,
            'weight' => $this->weight,
            'rubric' => $this->rubric,
        ];
    }
}
