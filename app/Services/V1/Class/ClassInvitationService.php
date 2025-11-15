<?php

namespace App\Services\V1\Class;

use App\Models\ClassInvitation;
use App\Models\ClassEnrollment;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Support\Str;

class ClassInvitationService
{
    /**
     * Create a class invitation.
     */
    public function create(array $data, User $user): ClassInvitation
    {
        $class = Classes::where('class_code', $data['class_code'])->firstOrFail();

        $student = User::where('email', $data['email'])
            ->where('role', 'student')
            ->firstOrFail();

  
        if (
            ClassEnrollment::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists()
        ) {
            throw new \Exception("Student already enrolled in this class", 409);
        }

  
        if (
            ClassInvitation::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists()
        ) {
            throw new \Exception("Invitation already sent", 409);
        }

        return ClassInvitation::create([
            'teacher_id' => $user->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'email' => $student->email,
            'invitation_token' => Str::random(32),
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Update an invitation's status (accept / decline).
     */
    public function updateStatus(ClassInvitation $invitation, string $status): ClassInvitation
    {
        $invitation->update(['status' => $status]);

        if ($status === 'accepted') {
            ClassEnrollment::firstOrCreate(
                [
                    'class_id' => $invitation->class_id,
                    'student_id' => $invitation->student_id,
                ],
                [
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]
            );
        }

        return $invitation;
    }

    /**
     * Delete an invitation.
     */
    public function delete(ClassInvitation $invitation): bool
    {
        return $invitation->delete();
    }
}
