<?php

namespace App\Notifications;

use App\Models\StudentAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $studentAssignment;

    /**
     * Create a new notification instance.
     */
    public function __construct(StudentAssignment $studentAssignment)
    {
        $this->studentAssignment = $studentAssignment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $title = 'Untitled Task';
        if ($this->studentAssignment->test) {
            $title = $this->studentAssignment->test->title;
        } elseif ($this->studentAssignment->assignment) {
            $title = $this->studentAssignment->assignment->getAssignmentTitle();
        }

        return (new MailMessage)
            ->subject('Task Graded: ' . $title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your teacher has finished grading your task: "' . $title . '".')
            ->line('Your score is: ' . $this->studentAssignment->score)
            ->action('View Feedback', url('/student/dashboard'))
            ->line('Keep up the good work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = 'Untitled Task';
        if ($this->studentAssignment->test) {
            $title = $this->studentAssignment->test->title;
        } elseif ($this->studentAssignment->assignment) {
            $title = $this->studentAssignment->assignment->getAssignmentTitle();
        }

        return [
            'assignment_id' => $this->studentAssignment->id,
            'title' => $title,
            'score' => $this->studentAssignment->score,
            'message' => 'Your teacher has graded your task: ' . $title,
            'type' => 'task_graded'
        ];
    }
}

