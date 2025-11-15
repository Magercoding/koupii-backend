<?php

namespace App\Http\Resources\V1\Class;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassInvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,

            'class' => [
                'id' => $this->class->id,
                'name' => $this->class->name,
            ],

            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'avatar' => $this->student->avatar ? url($this->student->avatar) : null,
            ],

            'teacher' => $this->teacher ? [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
            ] : null,
        ];
    }

}
