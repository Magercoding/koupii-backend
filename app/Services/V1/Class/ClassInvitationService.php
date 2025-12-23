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
     * Update invitation details (e.g., change email if wrong person was invited).
     */
    public function updateInvitation(ClassInvitation $invitation, array $data): ClassInvitation
    {
        // If email is being changed, find the new student
        if (isset($data['email']) && $data['email'] !== $invitation->email) {
            $student = User::where('email', $data['email'])
                ->where('role', 'student')
                ->firstOrFail();

            // Check if student is already enrolled
            if (
                ClassEnrollment::where('class_id', $invitation->class_id)
                    ->where('student_id', $student->id)
                    ->exists()
            ) {
                throw new \Exception("Student already enrolled in this class", 409);
            }

            // Check if there's already an invitation for this student
            if (
                ClassInvitation::where('class_id', $invitation->class_id)
                    ->where('student_id', $student->id)
                    ->where('id', '!=', $invitation->id) // Exclude current invitation
                    ->exists()
            ) {
                throw new \Exception("Invitation already sent to this student", 409);
            }

            $invitation->update([
                'student_id' => $student->id,
                'email' => $student->email,
                'invitation_token' => Str::random(32), // Generate new token
                'expires_at' => now()->addDay(), // Reset expiry
                'status' => 'pending', // Reset status
            ]);
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
