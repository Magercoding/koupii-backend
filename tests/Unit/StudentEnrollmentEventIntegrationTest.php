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

class StudentEnrollmentEventIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_listener_integration_creates_assignments_for_newly_enrolled_student()
    {
        // Create users and class
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        // Create some assignments for the class
        $assignment1 = Assignment::factory()->create([
            'class_id' => $class->id,
            'is_published' => true
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'class_id' => $class->id,
            'is_published' => true
        ]);

        // Create unpublished assignment (should not create student assignment)
        $unpublishedAssignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'is_published' => false
        ]);

        // Verify no student assignments exist initially
        $this->assertEquals(0, StudentAssignment::where('student_id', $student->id)->count());

        // Dispatch the event (simulating what happens in the controller)
        StudentEnrolledInClass::dispatch($student, $class);

        // Verify student assignments were created for published assignments
        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment1->id,
            'student_id' => $student->id,
            'status' => 'not_started'
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment2->id,
            'student_id' => $student->id,
            'status' => 'not_started'
        ]);

        // Verify no student assignment for unpublished assignment
        $this->assertDatabaseMissing('student_assignments', [
            'assignment_id' => $unpublishedAssignment->id,
            'student_id' => $student->id
        ]);

        // Verify total count
        $this->assertEquals(2, StudentAssignment::where('student_id', $student->id)->count());
    }

    public function test_event_listener_does_not_create_duplicate_assignments()
    {
        // Create users and class
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        // Create assignment
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'is_published' => true
        ]);

        // Create existing student assignment
        StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'test_id' => $assignment->test_id,
            'assignment_type' => $assignment->type,
            'status' => 'not_started',
            'assigned_at' => now(),
            'attempt_number' => 1,
            'attempt_count' => 0,
            'time_spent_minutes' => 0
        ]);

        // Verify one assignment exists
        $this->assertEquals(1, StudentAssignment::where('student_id', $student->id)->count());

        // Dispatch the event again
        StudentEnrolledInClass::dispatch($student, $class);

        // Verify still only one assignment exists (no duplicate created)
        $this->assertEquals(1, StudentAssignment::where('student_id', $student->id)->count());
    }

    public function test_event_listener_handles_class_with_no_assignments()
    {
        // Create users and class with no assignments
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        // Verify no assignments exist for the class
        $this->assertEquals(0, Assignment::where('class_id', $class->id)->count());

        // Dispatch the event (should not throw exception)
        StudentEnrolledInClass::dispatch($student, $class);

        // Verify no student assignments were created
        $this->assertEquals(0, StudentAssignment::where('student_id', $student->id)->count());
    }

    public function test_multiple_students_enrollment_creates_assignments_for_all()
    {
        // Create users and class
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        // Create assignment
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'is_published' => true
        ]);

        // Enroll all students
        StudentEnrolledInClass::dispatch($student1, $class);
        StudentEnrolledInClass::dispatch($student2, $class);
        StudentEnrolledInClass::dispatch($student3, $class);

        // Verify assignments were created for all students
        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment->id,
            'student_id' => $student1->id,
            'status' => 'not_started'
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment->id,
            'student_id' => $student2->id,
            'status' => 'not_started'
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment->id,
            'student_id' => $student3->id,
            'status' => 'not_started'
        ]);

        // Verify total count
        $this->assertEquals(3, StudentAssignment::where('assignment_id', $assignment->id)->count());
    }
}