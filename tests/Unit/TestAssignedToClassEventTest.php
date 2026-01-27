<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\TestAssignedToClass;
use App\Listeners\CreateAssignmentsForTest;
use App\Models\User;
use App\Models\Classes;
use App\Models\Test;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;

class TestAssignedToClassEventTest extends TestCase
{
    use RefreshDatabase;

    protected $teacher;
    protected $class;
    protected $test;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->class = Classes::factory()->create(['teacher_id' => $this->teacher->id]);
        $this->test = Test::factory()->create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'is_published' => true
        ]);
    }

    public function test_event_is_dispatched_when_test_assigned_to_class()
    {
        Event::fake();

        // Dispatch the event
        TestAssignedToClass::dispatch($this->test, $this->class, [
            'due_date' => now()->addDays(7),
            'title' => 'Test Assignment'
        ]);

        // Assert the event was dispatched
        Event::assertDispatched(TestAssignedToClass::class, function ($event) {
            return $event->test->id === $this->test->id &&
                   $event->class->id === $this->class->id &&
                   isset($event->options['due_date']);
        });
    }

    public function test_listener_creates_assignment_when_event_fired()
    {
        // Create some enrolled students
        $students = User::factory()->count(3)->create(['role' => 'student']);
        foreach ($students as $student) {
            ClassEnrollment::create([
                'class_id' => $this->class->id,
                'student_id' => $student->id,
                'status' => 'active'
            ]);
        }

        // Dispatch the event
        TestAssignedToClass::dispatch($this->test, $this->class, [
            'due_date' => now()->addDays(7),
            'title' => 'Test Assignment',
            'description' => 'Complete this test'
        ]);

        // Assert assignment was created
        $this->assertDatabaseHas('assignments', [
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'source_type' => 'auto_test'
        ]);

        // Assert student assignments were created
        $assignment = Assignment::where('test_id', $this->test->id)->first();
        $this->assertEquals(3, $assignment->studentAssignments()->count());

        // Assert all student assignments have correct initial status
        foreach ($assignment->studentAssignments as $studentAssignment) {
            $this->assertEquals('not_started', $studentAssignment->status);
        }
    }

    public function test_listener_handles_class_with_no_students()
    {
        // Dispatch the event with no enrolled students
        TestAssignedToClass::dispatch($this->test, $this->class, [
            'due_date' => now()->addDays(7),
            'title' => 'Test Assignment'
        ]);

        // Assert assignment was created
        $this->assertDatabaseHas('assignments', [
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'source_type' => 'auto_test'
        ]);

        // Assert no student assignments were created
        $assignment = Assignment::where('test_id', $this->test->id)->first();
        $this->assertEquals(0, $assignment->studentAssignments()->count());
    }

    public function test_listener_only_creates_assignments_for_active_enrollments()
    {
        // Create students with different enrollment statuses
        $activeStudent = User::factory()->create(['role' => 'student']);
        $inactiveStudent = User::factory()->create(['role' => 'student']);
        $pendingStudent = User::factory()->create(['role' => 'student']);

        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $activeStudent->id,
            'status' => 'active'
        ]);

        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $inactiveStudent->id,
            'status' => 'inactive'
        ]);

        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $pendingStudent->id,
            'status' => 'pending'
        ]);

        // Dispatch the event
        TestAssignedToClass::dispatch($this->test, $this->class, [
            'due_date' => now()->addDays(7),
            'title' => 'Test Assignment'
        ]);

        // Assert only one student assignment was created (for active student)
        $assignment = Assignment::where('test_id', $this->test->id)->first();
        $this->assertEquals(1, $assignment->studentAssignments()->count());
        
        // Assert it was created for the active student
        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment->id,
            'student_id' => $activeStudent->id,
            'status' => 'not_started'
        ]);
    }
}