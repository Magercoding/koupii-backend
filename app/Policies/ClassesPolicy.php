<?php

namespace App\Policies;

use App\Models\Classes;
use App\Models\User;

class ClassesPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Classes $class): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher' && $class->hasTeacher($user->id)) {
            return true;
        }

        if ($user->role === 'student') {
            return $class->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'teacher';
    }

    public function update(User $user, Classes $class): bool
    {
        return $user->role === 'admin' || ($user->role === 'teacher' && $class->hasTeacher($user->id));
    }

    public function delete(User $user, Classes $class): bool
    {
        // Only the owner (or admin) can delete the class
        return $user->role === 'admin' || ($user->role === 'teacher' && $class->teacher_id === $user->id);
    }
}
