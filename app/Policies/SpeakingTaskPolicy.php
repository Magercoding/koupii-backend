<?php

namespace App\Policies;

use App\Models\SpeakingTask;
use App\Models\User;

class SpeakingTaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SpeakingTask $task): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            return $task->created_by === $user->id;
        }

        // Students can view if published and assigned
        if ($user->role === 'student') {
            return $task->is_published;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'teacher';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SpeakingTask $task): bool
    {
        return $user->role === 'admin' || ($user->role === 'teacher' && $task->created_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SpeakingTask $task): bool
    {
        return $user->role === 'admin' || ($user->role === 'teacher' && $task->created_by === $user->id);
    }
}
