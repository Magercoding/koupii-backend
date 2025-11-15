<?php



namespace App\Policies;

use App\Models\User;
use App\Models\ClassInvitation;

class ClassInvitationPolicy
{
    public function delete(User $user, ClassInvitation $invitation)
    {
        return $user->role === 'admin'
            || ($user->role === 'teacher' && $invitation->class->teacher_id === $user->id);
    }

    public function update(User $user, ClassInvitation $invitation)
    {
        // only student can accept/decline their own invitation
        if ($user->role === 'student') {
            return $invitation->student_id === $user->id;
        }

        // admin or teacher who owns the class
        return $user->role === 'admin'
            || ($user->role === 'teacher' && $invitation->class->teacher_id === $user->id);
    }
}
