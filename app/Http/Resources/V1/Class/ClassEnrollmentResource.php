<?php

namespace App\Http\Resources\V1\Class;

use App\Http\Resources\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassEnrollmentResource extends JsonResource
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
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at,
            'class' => new ClassResource($this->whenLoaded('class')),
            'student' => new UserResource($this->whenLoaded('student')),
        ];

    }
}
