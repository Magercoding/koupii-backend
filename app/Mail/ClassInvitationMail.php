<?php

namespace App\Mail;

use App\Models\ClassInvitation;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClassInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ClassInvitation $invitation;
    public Classes $class;
    public User $teacher;
    public User $student;

    /**
     * Create a new message instance.
     */
    public function __construct(ClassInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->class = $invitation->class;
        $this->teacher = $invitation->teacher;
        $this->student = $invitation->student;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->class->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.class-invitation',
            text: 'emails.class-invitation-text',
            with: [
                'invitation' => $this->invitation,
                'class' => $this->class,
                'teacher' => $this->teacher,
                'student' => $this->student,
                'acceptUrl' => $this->getAcceptUrl(),
                'declineUrl' => $this->getDeclineUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the URL to accept the invitation
     */
    private function getAcceptUrl(): string
    {
        return config('app.frontend_url') . '/invitations/accept/' . $this->invitation->invitation_token;
    }

    /**
     * Get the URL to decline the invitation
     */
    private function getDeclineUrl(): string
    {
        return config('app.frontend_url') . '/invitations/decline/' . $this->invitation->invitation_token;
    }
}
