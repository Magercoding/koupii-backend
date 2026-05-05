<?php

namespace App\Mail;

use App\Models\Classes;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoTeacherInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Classes $class;
    public User $inviter;
    public string $recipientEmail;

    public function __construct(Classes $class, User $inviter, string $recipientEmail)
    {
        $this->class = $class;
        $this->inviter = $inviter;
        $this->recipientEmail = $recipientEmail;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to co-teach {$this->class->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.co-teacher-invitation',
            with: [
                'class'          => $this->class,
                'inviter'        => $this->inviter,
                'recipientEmail' => $this->recipientEmail,
                'joinUrl'        => config('app.frontend_url') . '/dashboard/class',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
