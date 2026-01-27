<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\V1\Assignment\AssignmentFactory;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\Classes;
use App\Models\User;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AssignmentFactoryTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentFactory $assignmentFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentFactory = new AssignmentFactory();
    }

    public function test_creates_assignment_for_reading_test()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'reading',
            'title' => 'Reading Test 1',
            'is_published' => true
        ]);

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test);

        // Assert
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals($test->title, $assignment->title);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals('auto_test', $assignment->source_type);
        $this->assertEquals($test->id, $assignment->source_id);
        $this->assertEquals($test->id, $assignment->test_id);
        $this->assertEquals($class->id, $assignment->class_id);
        $this->assertTrue($assignment->is_published);
        $this->assertNotNull($assignment->auto_created_at);
    }

    public function test_creates_assignment_for_writing_test()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'writing',
            'title' => 'Writing Test 1',
            'is_published' => true
        ]);

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test);

        // Assert
        $this->assertEquals('writing_task', $assignment->type);
        $this->assertEquals($test->title, $assignment->title);
    }

    public function test_creates_assignment_for_listening_test()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'listening',
            'title' => 'Listening Test 1',
            'is_published' => true
        ]);

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test);

        // Assert
        $this->assertEquals('listening_task', $assignment->type);
    }

    public function test_creates_assignment_for_speaking_test()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'speaking',
            'title' => 'Speaking Test 1',
            'is_published' => true
        ]);

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test);

        // Assert
        $this->assertEquals('speaking_task', $assignment->type);
    }

    public function test_creates_assignment_with_custom_options()
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

        $customOptions = [
            'title' => 'Custom Assignment Title',
            'description' => 'Custom description',
            'due_date' => now()->addDays(14),
            'is_published' => false,
            'settings' => ['custom' => 'setting']
        ];

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test, $customOptions);

        // Assert
        $this->assertEquals('Custom Assignment Title', $assignment->title);
        $this->assertEquals('Custom description', $assignment->description);
        $this->assertEquals($customOptions['due_date']->format('Y-m-d H:i:s'), $assignment->due_date->format('Y-m-d H:i:s'));
        $this->assertFalse($assignment->is_published);
        $this->assertEquals(['custom' => 'setting'], $assignment->assignment_settings);
    }

    public function test_throws_exception_for_unpublished_test()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'reading',
            'is_published' => false // Not published
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be auto-assigned');
        
        $this->assignmentFactory->createFromTest($test);
    }

    public function test_throws_exception_for_test_without_class()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $test = Test::factory()->create([
            'class_id' => null, // No class assigned
            'creator_id' => $teacher->id,
            'type' => 'reading',
            'is_published' => true
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be auto-assigned');
        
        $this->assignmentFactory->createFromTest($test);
    }

    public function test_creates_student_assignments_for_enrolled_students()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'type' => 'reading_task'
        ]);

        // Create enrolled students
        $students = User::factory()->count(3)->create(['role' => 'student']);
        foreach ($students as $student) {
            ClassEnrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active'
            ]);
        }

        // Act
        $studentCount = $this->assignmentFactory->createStudentAssignments($assignment);

        // Assert
        $this->assertEquals(3, $studentCount);
        
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(3, $studentAssignments);
        
        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals($assignment->id, $studentAssignment->assignment_id);
            $this->assertEquals($assignment->type, $studentAssignment->assignment_type);
            $this->assertEquals(StudentAssignment::STATUS_NOT_STARTED, $studentAssignment->status);
            $this->assertEquals(1, $studentAssignment->attempt_number);
            $this->assertEquals(0, $studentAssignment->attempt_count);
        }
    }

    public function test_creates_no_student_assignments_for_empty_class()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'type' => 'reading_task'
        ]);

        // No enrolled students

        // Act
        $studentCount = $this->assignmentFactory->createStudentAssignments($assignment);

        // Assert
        $this->assertEquals(0, $studentCount);
        $this->assertCount(0, StudentAssignment::where('assignment_id', $assignment->id)->get());
    }

    public function test_only_creates_assignments_for_active_enrollments()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'type' => 'reading_task'
        ]);

        // Create students with different enrollment statuses
        $activeStudent = User::factory()->create(['role' => 'student']);
        $inactiveStudent = User::factory()->create(['role' => 'student']);
        
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $activeStudent->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $inactiveStudent->id,
            'status' => 'inactive'
        ]);

        // Act
        $studentCount = $this->assignmentFactory->createStudentAssignments($assignment);

        // Assert
        $this->assertEquals(1, $studentCount); // Only active student
        
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(1, $studentAssignments);
        $this->assertEquals($activeStudent->id, $studentAssignments->first()->student_id);
    }

    public function test_handles_unknown_test_type()
    {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $test = Test::factory()->create([
            'class_id' => $class->id,
            'creator_id' => $teacher->id,
            'type' => 'unknown_type',
            'is_published' => true
        ]);

        // Act
        $assignment = $this->assignmentFactory->createFromTest($test);

        // Assert
        $this->assertEquals('general_task', $assignment->type);
    }
}