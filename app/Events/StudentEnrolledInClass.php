<?php

namespace App\Events;

use App\Models\User;
use App\Models\Classes;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a student is enrolled in a class
 * Triggers automatic assignment creation for existing class assignments
 */
class StudentEnrolledInClass
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $student;
    public Classes $class;

    /**
     * Create a new event instance.
     *
     * @param User $student The student being enrolled
     * @param Classes $class The class the student is joining
     */
    public function __construct(User $student, Classes $class)
    {
        $this->student = $student;
        $this->class = $class;
    }
}