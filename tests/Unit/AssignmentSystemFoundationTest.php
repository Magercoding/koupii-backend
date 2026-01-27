<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Models\Test;
use App\Models\Classes;
use App\Models\User;
use App\Events\TestAssignedToClass;
use App\Events\StudentEnrolledInClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class AssignmentSystemFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        
        // Create test class
        $this->class = Classes::factory()->create(['teacher_id' => $this->teacher->id]);
        
        // Create test
        $this->test = Test::factory()->create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'type' => 'reading',
            'title' => 'Sample Reading Test'
        ]);
    }

    public function test_assignment_model_has_enhanced_attributes()
    {
        $assignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'source_type' => 'auto_test',
            'source_id' => $this->test->id,
            'type' => 'reading_task',
            'auto_created_at' => now(),
            'is_published' => true
        ]);

        $this->assertTrue($assignment->isAutoCreated());
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals($this->test->id, $assignment->source_id);
        $this->assertNotNull($assignment->auto_created_at);
    }

    public function test_student_assignment_model_basic_functionality()
    {
        $assignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'is_published' => true
        ]);

        $studentAssignment = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'assignment_type' => 'reading_task',
            'status' => 'not_started'
        ]);

        $this->assertEquals('not_started', $studentAssignment->status);
        $this->assertEquals('reading_task', $studentAssignment->assignment_type);
        $this->assertEquals($assignment->id, $studentAssignment->assignment_id);
        $this->assertEquals($this->student->id, $studentAssignment->student_id);
    }

    public function test_test_model_enhanced_methods()
    {
        $this->assertTrue($this->test->canBeAutoAssigned());
        $this->assertEquals('Sample Reading Test', $this->test->getAssignmentTitle());
        $this->assertEquals('reading_task', $this->test->getAssignmentType());
        $this->assertInstanceOf(\Carbon\Carbon::class, $this->test->getDefaultDueDate());
    }

    public function test_events_are_registered()
    {
        Event::fake();

        // Test that events can be dispatched
        event(new TestAssignedToClass($this->test, $this->class));
        event(new StudentEnrolledInClass($this->student, $this->class));

        Event::assertDispatched(TestAssignedToClass::class);
        Event::assertDispatched(StudentEnrolledInClass::class);
    }

    public function test_assignment_scopes()
    {
        // Create auto-created assignment
        $autoAssignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Auto Assignment',
            'source_type' => 'auto_test',
            'source_id' => $this->test->id,
            'type' => 'reading_task',
            'is_published' => true
        ]);

        // Create manual assignment
        $manualAssignment = Assignment::create([
            'class_id' => $this->class->id,
            'title' => 'Manual Assignment',
            'source_type' => 'manual',
            'is_published' => true
        ]);

        // Test scopes
        $this->assertEquals(1, Assignment::autoCreated()->count());
        $this->assertEquals(1, Assignment::manuallyCreated()->count());
        $this->assertEquals(1, Assignment::byType('reading_task')->count());
        $this->assertEquals(2, Assignment::forClass($this->class->id)->count());
        $this->assertEquals(2, Assignment::published()->count());
    }

    public function test_student_assignment_basic_scopes()
    {
        $assignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'is_published' => true
        ]);

        $studentAssignment = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'assignment_type' => 'reading_task',
            'status' => 'not_started'
        ]);

        // Test basic scopes
        $this->assertEquals(1, StudentAssignment::forStudent($this->student->id)->count());
        $this->assertEquals(1, StudentAssignment::forAssignment($assignment->id)->count());
        $this->assertEquals(1, StudentAssignment::byStatus('not_started')->count());
    }

    public function test_assignment_relationships()
    {
        $assignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'is_published' => true
        ]);

        // Test relationships
        $this->assertEquals($this->class->id, $assignment->class->id);
        $this->assertEquals($this->test->id, $assignment->test->id);
        
        // Test assignment belongs to class
        $this->assertInstanceOf(Classes::class, $assignment->class);
        $this->assertInstanceOf(Test::class, $assignment->test);
    }

    public function test_student_assignment_relationships()
    {
        $assignment = Assignment::create([
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Test Assignment',
            'is_published' => true
        ]);

        $studentAssignment = StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'assignment_type' => 'reading_task',
            'status' => 'not_started'
        ]);

        // Test relationships
        $this->assertEquals($assignment->id, $studentAssignment->assignment->id);
        $this->assertEquals($this->student->id, $studentAssignment->student->id);
        
        // Test student assignment belongs to assignment and student
        $this->assertInstanceOf(Assignment::class, $studentAssignment->assignment);
        $this->assertInstanceOf(User::class, $studentAssignment->student);
    }
}