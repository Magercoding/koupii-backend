<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Test;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use App\Events\TestAssignedToClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SimpleAutomaticAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_creates_automatic_assignment()
    {
        // Arrange: Create teacher, class, and students
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Enroll students in class
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student1->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student2->id,
            'status' => 'active'
        ]);

        // Create a test
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => true,
            'type' => 'reading',
            'title' => 'Test Assignment'
        ]);

        // Act: Dispatch the event manually
        TestAssignedToClass::dispatch($test, $class, [
            'title' => 'Reading Assignment',
            'description' => 'Complete this reading test',
            'due_date' => now()->addDays(7),
            'is_published' => true
        ]);

        // Assert: Assignment was automatically created
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment, 'Assignment should be automatically created');
        $this->assertEquals($class->id, $assignment->class_id);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals('auto_test', $assignment->source_type);
        $this->assertEquals('Reading Assignment', $assignment->title);
        
        // Assert: Student assignments were created
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(2, $studentAssignments, 'Should create assignments for both students');
        
        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals('not_started', $studentAssignment->status);
            $this->assertEquals('reading_task', $studentAssignment->assignment_type);
        }
    }

    public function test_assignment_api_returns_unified_assignments()
    {
        // Arrange: Create teacher, class, and assignment
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => true,
            'type' => 'reading',
            'title' => 'Test Assignment'
        ]);

        $assignment = Assignment::factory()->create([
            'class_id' => $class->id,
            'test_id' => $test->id,
            'type' => 'reading_task',
            'source_type' => 'auto_test',
            'title' => 'Reading Assignment'
        ]);

        // Act: Get assignments for the class
        $response = $this->actingAs($teacher)
            ->getJson("/api/v1/assignments/class/{$class->id}");

        // Assert: API returns the assignment
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data, 'Should return one assignment');
        $this->assertEquals($assignment->id, $data[0]['id']);
        $this->assertEquals('reading_task', $data[0]['type']);
        $this->assertEquals('Test Assignment', $data[0]['task']['title']);
    }
}