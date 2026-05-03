<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->assignment->getAssignmentTitle();
        $className = $this->assignment->class ? $this->assignment->class->name : 'your class';

        return (new MailMessage)
            ->subject('New Assignment: ' . $title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new assignment has been posted in ' . $className . '.')
            ->line('Assignment: ' . $title)
            ->line('Due Date: ' . ($this->assignment->due_date ? $this->assignment->due_date->format('M d, Y H:i') : 'No deadline'))
            ->action('View Assignment', url('/student/dashboard'))
            ->line('Good luck with your studies!');
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->assignment->getAssignmentTitle();

        return [
            'assignment_id' => $this->assignment->id,
            'title' => 'New Assignment: ' . $title,
            'message' => 'New assignment posted in ' . ($this->assignment->class->name ?? 'class') . ': ' . $title,
            'type' => 'assignment',
            'due_date' => $this->assignment->due_date
        ];
    }
}
