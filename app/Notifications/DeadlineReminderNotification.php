<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('Deadline Reminder: ' . $title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('This is a friendly reminder that the deadline for your assignment "' . $title . '" is approaching.')
            ->line('Due Date: ' . ($this->assignment->due_date ? $this->assignment->due_date->format('M d, Y H:i') : 'Unknown'))
            ->action('Finish Assignment', url('/student/dashboard'))
            ->line('Please make sure to submit your work on time!');
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->assignment->getAssignmentTitle();

        return [
            'assignment_id' => $this->assignment->id,
            'title' => 'Deadline Reminder: ' . $title,
            'message' => 'Your assignment "' . $title . '" is due soon!',
            'type' => 'reminder',
            'due_date' => $this->assignment->due_date
        ];
    }
}
