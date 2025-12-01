<?php

namespace App\Services\V1\Class;

use App\Models\Classes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Helpers\FileUploadHelper;

class ClassService
{
    public function create(array $data)
    {
        $teacher = Auth::user();

     
        $exists = Classes::where('teacher_id', $teacher->id)
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return ['error' => 'Class name already exists', 'code' => 409];
        }

        if (isset($data['cover_image'])) {
            $data['cover_image'] = FileUploadHelper::upload($data['cover_image'], 'cover');
        }

        return Classes::create([
            'teacher_id' => $teacher->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'class_code' => $data['class_code'] ?? strtoupper(Str::random(8)),
            'cover_image' => $data['cover_image'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
    public function update(string $id, array $data)
    {
        $class = Classes::find($id);

        if (!$class) {
            return ['error' => 'Class not found', 'code' => 404];
        }

        $user = Auth::user();

        if ($user->role !== 'admin' && $class->teacher_id !== $user->id) {
            return ['error' => 'Unauthorized', 'code' => 403];
        }

   
        if (isset($data['name'])) {
            $exists = Classes::where('teacher_id', $user->id)
                ->where('name', $data['name'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return ['error' => 'Class name already exists', 'code' => 409];
            }
        }

        
        if (isset($data['cover_image'])) {

            if ($class->cover_image) {
                FileUploadHelper::delete($class->cover_image);
            }

            $data['cover_image'] = FileUploadHelper::upload($data['cover_image'], 'cover');
        }

        
        $class->update($data);

        return $class;
    }





}
