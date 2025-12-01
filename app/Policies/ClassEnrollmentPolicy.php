<?php

namespace App\Policies;

use App\Models\ClassEnrollment;
use App\Models\User;

class ClassEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'teacher', 'student']);
    }

    public function view(User $user, ClassEnrollment $enrollment): bool
    {
        return $this->canAccess($user, $enrollment);
    }

    public function update(User $user, ClassEnrollment $enrollment): bool
    {
        return $this->canAccess($user, $enrollment);
    }

    public function delete(User $user, ClassEnrollment $enrollment): bool
    {
        return $this->canAccess($user, $enrollment);
    }

    /**
     * Shared access logic
     */
    private function canAccess(User $user, ClassEnrollment $enrollment): bool
    {
        return match ($user->role) {
            'admin' => true,
            'teacher' => $enrollment->class->teacher_id === $user->id,
            'student' => $enrollment->student_id === $user->id,
            default => false,
        };
    }
}
