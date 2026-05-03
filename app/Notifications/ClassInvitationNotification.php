<?php

namespace App\Notifications;

use App\Models\Classes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $class;

    public function __construct(Classes $class)
    {
        $this->class = $class;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Class Invitation: ' . $this->class->name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have been invited to join the class: "' . $this->class->name . '".')
            ->line('Teacher: ' . ($this->class->teacher->name ?? 'Unknown'))
            ->action('Join Class', url('/student/dashboard'))
            ->line('We look forward to seeing you in class!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'class_id' => $this->class->id,
            'title' => 'Class Invitation',
            'message' => 'You have been invited to join class: ' . $this->class->name,
            'type' => 'reminder', // Using reminder type for invitation as it requires action
        ];
    }
}
