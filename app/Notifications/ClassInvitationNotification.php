<?php

namespace App\Notifications;

use App\Models\Classes;
use App\Models\ClassInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClassInvitationNotification extends Notification
{
    use Queueable;

    protected Classes $class;
    protected ?ClassInvitation $invitation;

    public function __construct(Classes $class, ?ClassInvitation $invitation = null)
    {
        $this->class = $class;
        $this->invitation = $invitation;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $teacherName = $this->class->teacher->name ?? 'Your teacher';

        return [
            'type'             => 'class_invitation',
            'title'            => 'Class Invitation',
            'message'          => "{$teacherName} invited you to join \"{$this->class->name}\"",
            'class_id'         => $this->class->id,
            'class_name'       => $this->class->name,
            'teacher_name'     => $teacherName,
            'invitation_id'    => $this->invitation?->id,
            'invitation_token' => $this->invitation?->invitation_token,
        ];
    }
}
