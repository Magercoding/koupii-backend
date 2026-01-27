<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\TestAssignedToClass;
use App\Models\User;
use App\Models\Classes;
use App\Models\Test;
use App\Models\Assignment;
use App\Models\ClassEnrollment;

class ClassTestAssignmentTest extends TestCase
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

    public function test_assign_to_class_endpoint_dispatches_event()
    {
        // Create enrolled students one by one to avoid factory issues
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student1->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active'
        ]);

        // Make the API request
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/classes/{$this->class->id}/tests/{$this->test->id}/assign", [
                'due_date' => now()->addDays(7)->toISOString(),
                'title' => 'Custom Assignment Title',
                'description' => 'Custom assignment description'
            ]);

        // Assert successful response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Test assigned to all students in ' . $this->class->name,
                'data' => [
                    'test_id' => $this->test->id,
                    'test_title' => $this->test->title,
                    'assigned_to_students' => 2
                ]
            ]);

        // Verify that assignment was created (which means event was processed)
        $this->assertDatabaseHas('assignments', [
            'class_id' => $this->class->id,
            'test_id' => $this->test->id,
            'title' => 'Custom Assignment Title',
            'source_type' => 'auto_test'
        ]);
    }

    public function test_assign_to_class_creates_assignment_and_student_assignments()
    {
        // Create enrolled students one by one
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student1->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student3->id,
            'status' => 'active'
        ]);

        // Make the API request
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/classes/{$this->class->id}/tests/{$this->test->id}/assign", [
                'due_date' => now()->addDays(7)->toISOString(),
                'title' => 'Test Assignment',
                'description' => 'Complete this test'
            ]);

        // Assert successful response
        $response->assertStatus(200);

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
    }

    public function test_assign_to_class_requires_published_test()
    {
        // Create unpublished test
        $unpublishedTest = Test::factory()->create([
            'creator_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'is_published' => false
        ]);

        // Make the API request
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/classes/{$this->class->id}/tests/{$unpublishedTest->id}/assign", [
                'due_date' => now()->addDays(7)->toISOString()
            ]);

        // Assert validation error
        $response->assertStatus(422)
            ->assertJsonFragment(['Test must be published before assignment']);
    }

    public function test_assign_to_class_requires_valid_due_date()
    {
        // Make the API request with past due date
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/classes/{$this->class->id}/tests/{$this->test->id}/assign", [
                'due_date' => now()->subDays(1)->toISOString()
            ]);

        // Assert validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_assign_to_class_requires_class_ownership()
    {
        // Create another teacher
        $otherTeacher = User::factory()->create(['role' => 'teacher']);

        // Make the API request as different teacher
        $response = $this->actingAs($otherTeacher)
            ->postJson("/api/v1/classes/{$this->class->id}/tests/{$this->test->id}/assign", [
                'due_date' => now()->addDays(7)->toISOString()
            ]);

        // Assert not found (class not owned by this teacher)
        $response->assertStatus(422);
    }
}