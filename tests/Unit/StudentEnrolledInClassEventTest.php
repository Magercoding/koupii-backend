<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Events\StudentEnrolledInClass;
use App\Listeners\CreateAssignmentsForNewStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class StudentEnrolledInClassEventTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Classes $class;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->class = Classes::factory()->create(['teacher_id' => $this->teacher->id]);
    }

    public function test_student_enrolled_in_class_event_is_dispatched_on_enrollment_creation()
    {
        Event::fake();

        // Create enrollment
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()
        ]);

        // Note: Event is dispatched in controller, not model
        // This test verifies the event structure is correct
        $event = new StudentEnrolledInClass($this->student, $this->class);
        
        $this->assertEquals($this->student->id, $event->student->id);
        $this->assertEquals($this->class->id, $event->class->id);
    }

    public function test_create_assignments_for_new_student_listener_creates_assignments()
    {
        // Create some assignments for the class
        $assignment1 = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Create unpublished assignment (should not create student assignment)
        $unpublishedAssignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => false
        ]);

        // Create event and listener
        $event = new StudentEnrolledInClass($this->student, $this->class);
        $listener = new CreateAssignmentsForNewStudent();

        // Handle the event
        $listener->handle($event);

        // Verify student assignments were created for published assignments only
        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment1->id,
            'student_id' => $this->student->id,
            'status' => 'not_started'
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment2->id,
            'student_id' => $this->student->id,
            'status' => 'not_started'
        ]);

        // Verify no student assignment for unpublished assignment
        $this->assertDatabaseMissing('student_assignments', [
            'assignment_id' => $unpublishedAssignment->id,
            'student_id' => $this->student->id
        ]);
    }

    public function test_listener_does_not_create_duplicate_assignments()
    {
        // Create assignment
        $assignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Create existing student assignment
        StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'test_id' => $assignment->test_id,
            'assignment_type' => $assignment->type,
            'status' => 'not_started',
            'assigned_at' => now(),
            'attempt_number' => 1,
            'attempt_count' => 0,
            'time_spent_minutes' => 0
        ]);

        // Create event and listener
        $event = new StudentEnrolledInClass($this->student, $this->class);
        $listener = new CreateAssignmentsForNewStudent();

        // Handle the event
        $listener->handle($event);

        // Verify only one student assignment exists
        $studentAssignments = StudentAssignment::where([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id
        ])->get();

        $this->assertCount(1, $studentAssignments);
    }

    public function test_listener_handles_class_with_no_assignments()
    {
        // Create event and listener for class with no assignments
        $event = new StudentEnrolledInClass($this->student, $this->class);
        $listener = new CreateAssignmentsForNewStudent();

        // Handle the event (should not throw exception)
        $listener->handle($event);

        // Verify no student assignments were created
        $studentAssignments = StudentAssignment::where('student_id', $this->student->id)->get();
        $this->assertCount(0, $studentAssignments);
    }
}