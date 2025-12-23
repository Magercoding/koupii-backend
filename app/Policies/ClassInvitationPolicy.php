<?php



namespace App\Policies;

use App\Models\User;
use App\Models\ClassInvitation;

class ClassInvitationPolicy
{
    public function create(User $user)
    {

        return in_array($user->role, ['admin', 'teacher']);
    }

    public function delete(User $user, ClassInvitation $invitation)
    {
        return $user->role === 'admin'
            || ($user->role === 'teacher' && $invitation->class->teacher_id === $user->id);
    }

    public function update(User $user, ClassInvitation $invitation)
    {
       
        if ($user->role === 'student') {
            return $invitation->student_id === $user->id;
        }

        
        return $user->role === 'admin'
            || ($user->role === 'teacher' && $invitation->class->teacher_id === $user->id);
    }
}
