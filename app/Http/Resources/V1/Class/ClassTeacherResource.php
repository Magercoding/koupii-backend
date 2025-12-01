<?php

namespace App\Http\Resources\V1\Class;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassTeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this)
            return null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'avatar' => $this->avatar ? url($this->avatar) : null,
        ];
    }
}
