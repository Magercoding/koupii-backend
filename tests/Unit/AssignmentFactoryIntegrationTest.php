<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Events\TestAssignedToClass;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\Classes;
use App\Models\User;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class AssignmentFactoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_assigned_to_class_event_creates_assignment_and_student_assignments()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'reading',
            'title' => 'Integration Test Reading',
            'is_published' => true
        ]);

        // Create enrolled students
        $students = User::factory()->count(2)->create(['role' => 'student']);
        foreach ($students as $student) {
            ClassEnrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active'
            ]);
        }

        // Act - Fire the event
        Event::dispatch(new TestAssignedToClass($test, $class, [
            'title' => 'Custom Assignment Title',
            'due_date' => now()->addDays(10)
        ]));

        // Assert - Check that assignment was created
        $assignment = Assignment::where('test_id', $test->id)
            ->where('class_id', $class->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertEquals('Custom Assignment Title', $assignment->title);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals('auto_test', $assignment->source_type);
        $this->assertEquals($test->id, $assignment->source_id);
        $this->assertNotNull($assignment->auto_created_at);

        // Assert - Check that student assignments were created
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(2, $studentAssignments);

        $studentIds = $students->pluck('id')->toArray();
        $assignmentStudentIds = $studentAssignments->pluck('student_id')->toArray();
        
        // Debug output
        $this->assertEquals(sort($studentIds), sort($assignmentStudentIds), 
            'Student IDs mismatch. Expected: ' . implode(', ', $studentIds) . 
            '. Actual: ' . implode(', ', $assignmentStudentIds));

        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals($assignment->id, $studentAssignment->assignment_id);
            $this->assertEquals('reading_task', $studentAssignment->assignment_type);
            $this->assertEquals(StudentAssignment::STATUS_NOT_STARTED, $studentAssignment->status);
        }
    }

    public function test_event_handles_different_test_types()
    {
        // Test data for different test types
        $testTypes = [
            'reading' => 'reading_task',
            'writing' => 'writing_task',
            'listening' => 'listening_task',
            'speaking' => 'speaking_task'
        ];

        foreach ($testTypes as $testType => $expectedAssignmentType) {
            // Arrange
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => ucfirst($testType) . ' Test',
                'is_published' => true
            ]);

            $student = User::factory()->create(['role' => 'student']);
            ClassEnrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active'
            ]);

            // Act
            Event::dispatch(new TestAssignedToClass($test, $class));

            // Assert
            $assignment = Assignment::where('test_id', $test->id)->first();
            $this->assertNotNull($assignment, "Assignment not created for test type: {$testType}");
            $this->assertEquals($expectedAssignmentType, $assignment->type, "Wrong assignment type for test type: {$testType}");

            $studentAssignment = StudentAssignment::where('assignment_id', $assignment->id)->first();
            $this->assertNotNull($studentAssignment, "Student assignment not created for test type: {$testType}");
            $this->assertEquals($expectedAssignmentType, $studentAssignment->assignment_type, "Wrong student assignment type for test type: {$testType}");
        }
    }

    public function test_event_with_no_enrolled_students_creates_assignment_but_no_student_assignments()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'reading',
            'is_published' => true
        ]);

        // No enrolled students

        // Act
        Event::dispatch(new TestAssignedToClass($test, $class));

        // Assert
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment);

        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(0, $studentAssignments);
    }
}