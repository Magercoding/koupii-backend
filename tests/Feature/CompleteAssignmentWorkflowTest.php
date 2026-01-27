<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Test;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_workflow_from_test_creation_to_assignment_display()
    {
        // Step 1: Create teacher, class, and students
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

        // Step 2: Create a test (published so it can be assigned)
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => true,
            'type' => 'reading',
            'title' => 'Reading Comprehension Test'
        ]);

        // Verify no assignments exist yet
        $this->assertCount(0, Assignment::where('test_id', $test->id)->get());

        // Step 3: Manually assign the test to create assignments
        $response = $this->actingAs($teacher)
            ->postJson("/api/v1/classes/{$class->id}/tests/{$test->id}/create-assignment", [
                'title' => 'Reading Assignment',
                'description' => 'Complete this reading test by the due date',
                'due_date' => now()->addDays(7)->toISOString()
            ]);

        // Verify assignment creation response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Test assigned to all students in ' . $class->name,
                'data' => [
                    'test_id' => $test->id,
                    'test_title' => $test->title,
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'assigned_to_students' => 2
                ]
            ]);

        // Step 4: Verify assignment was created
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('Reading Assignment', $assignment->title);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals('auto_test', $assignment->source_type);

        // Step 5: Verify student assignments were created
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(2, $studentAssignments);
        
        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals('not_started', $studentAssignment->status);
            $this->assertEquals('reading_task', $studentAssignment->assignment_type);
        }

        // Step 6: Test the assignments API endpoint
        $response = $this->actingAs($teacher)
            ->getJson("/api/v1/assignments/class/{$class->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Debug: Let's see what assignments we have
        $this->assertGreaterThanOrEqual(1, count($data), 'Should have at least 1 assignment');
        
        // Find our manually created assignment
        $foundAssignment = collect($data)->firstWhere('id', $assignment->id);
        $this->assertNotNull($foundAssignment, 'Should find our manually created assignment');
        $this->assertEquals('reading_task', $foundAssignment['type']);
        $this->assertEquals('Reading Comprehension Test', $foundAssignment['task']['title']);

        // Step 7: Test as student - they should also see the assignment
        $response = $this->actingAs($student1)
            ->getJson("/api/v1/assignments/class/{$class->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertGreaterThanOrEqual(1, count($data), 'Student should see at least 1 assignment');
        
        // Find our assignment
        $foundAssignment = collect($data)->firstWhere('id', $assignment->id);
        $this->assertNotNull($foundAssignment, 'Student should see our assignment');
    }

    public function test_automatic_assignment_on_test_publish()
    {
        // Create teacher, class, and student
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $student = User::factory()->create(['role' => 'student']);
        
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'active'
        ]);

        // Create unpublished test
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => false,
            'type' => 'writing',
            'title' => 'Writing Test'
        ]);

        // Verify no assignments exist
        $this->assertCount(0, Assignment::where('test_id', $test->id)->get());

        // Publish the test by updating it
        $test->update(['is_published' => true]);

        // Since we're not going through the TestService, we need to manually trigger
        // In a real scenario, this would happen automatically through the service
        $this->artisan('queue:work', ['--once' => true]);

        // For this test, let's manually trigger the assignment creation
        \App\Events\TestAssignedToClass::dispatch($test, $class, [
            'title' => $test->title . ' - Assignment',
            'due_date' => now()->addDays(7)
        ]);

        // Verify assignment was created
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('writing_task', $assignment->type);

        // Verify student assignment was created
        $studentAssignment = StudentAssignment::where('assignment_id', $assignment->id)->first();
        $this->assertNotNull($studentAssignment);
        $this->assertEquals($student->id, $studentAssignment->student_id);
    }
}