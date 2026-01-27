<?php

namespace App\Services\V1\Class;

use App\Models\ClassInvitation;
use App\Models\ClassEnrollment;
use App\Models\Classes;
use App\Models\User;
use App\Mail\ClassInvitationMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ClassInvitationService
{
    /**
     * Create a class invitation and send email.
     */
    public function create(array $data, User $user): ClassInvitation
    {
        $class = Classes::where('class_code', $data['class_code'])->firstOrFail();

        $student = User::where('email', $data['email'])
            ->where('role', 'student')
            ->firstOrFail();

        // Check if student is already enrolled
        if (
            ClassEnrollment::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists()
        ) {
            throw new \Exception("Student already enrolled in this class", 409);
        }

        // Check if invitation already exists
        if (
            ClassInvitation::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists()
        ) {
            throw new \Exception("Invitation already sent", 409);
        }

        $invitation = ClassInvitation::create([
            'teacher_id' => $user->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'email' => $student->email,
            'invitation_token' => Str::random(32),
            'expires_at' => now()->addDay(),
        ]);

        // Send invitation email
        $this->sendInvitationEmail($invitation);

        return $invitation;
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
     * Update invitation details and resend email if needed.
     */
    public function updateInvitation(ClassInvitation $invitation, array $data): ClassInvitation
    {
        $emailChanged = false;

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

            $emailChanged = true;
        }

        // Resend email if email was changed
        if ($emailChanged) {
            $this->sendInvitationEmail($invitation->fresh());
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

    /**
     * Send invitation email to the student.
     */
    private function sendInvitationEmail(ClassInvitation $invitation): void
    {
        try {
            Mail::to($invitation->student->email)
                ->send(new ClassInvitationMail($invitation));

            Log::info('Class invitation email sent successfully', [
                'invitation_id' => $invitation->id,
                'student_email' => $invitation->student->email,
                'class_name' => $invitation->class->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send class invitation email', [
                'invitation_id' => $invitation->id,
                'student_email' => $invitation->student->email,
                'error' => $e->getMessage(),
            ]);

            // Don't throw the exception to avoid breaking the invitation creation
            // The invitation is still created, just the email failed
        }
    }

    /**
     * Resend invitation email.
     */
    public function resendInvitation(ClassInvitation $invitation): void
    {
        if ($invitation->status !== 'pending') {
            throw new \Exception("Can only resend pending invitations", 400);
        }

        if ($invitation->expires_at->isPast()) {
            // Extend expiry if expired
            $invitation->update([
                'expires_at' => now()->addDay(),
            ]);
        }

        $this->sendInvitationEmail($invitation);
    }
}
