<?php

namespace App\Http\Resources\V1\Class;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $showCode = in_array($user->role, ['admin', 'teacher']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover_image' => $this->cover_image ? url($this->cover_image) : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'teacher' => new ClassTeacherResource($this->teacher),

            'students' => ClassStudentResource::collection($this->students),

            $this->mergeWhen($showCode, [
                'class_code' => $this->class_code,
            ]),
        ];
    }
}
