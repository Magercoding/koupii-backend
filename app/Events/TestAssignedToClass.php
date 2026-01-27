<?php

namespace App\Events;

use App\Models\Test;
use App\Models\Classes;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a test is assigned to a class
 * Triggers automatic assignment creation for all enrolled students
 */
class TestAssignedToClass
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Test $test;
    public Classes $class;
    public array $options;

    /**
     * Create a new event instance.
     *
     * @param Test $test The test being assigned
     * @param Classes $class The class receiving the assignment
     * @param array $options Additional options for assignment creation (due_date, instructions, etc.)
     */
    public function __construct(Test $test, Classes $class, array $options = [])
    {
        $this->test = $test;
        $this->class = $class;
        $this->options = $options;
    }
}