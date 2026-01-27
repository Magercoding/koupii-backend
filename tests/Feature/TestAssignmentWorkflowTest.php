<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Test;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;

class TestAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_test_assignment_workflow()
    {
        // Arrange: Create teacher, class, students, and test
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        
        // Enroll students in class
        ClassEnrollment::create([
            'class_id' => $class->id,
            'student_id' => $student1->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::create([
            'class_id' => $class->id,
            'student_id' => $student2->id,
            'status' => 'active'
        ]);
        
        ClassEnrollment::create([
            'class_id' => $class->id,
            'student_id' => $student3->id,
            'status' => 'inactive' // This student should not get assignment
        ]);
        
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => true,
            'type' => 'reading',
            'title' => 'Reading Comprehension Test'
        ]);

        // Act: Assign test to class via API
        $response = $this->actingAs($teacher)
            ->postJson("/api/v1/classes/{$class->id}/tests/{$test->id}/assign", [
                'due_date' => now()->addDays(7)->toISOString(),
                'title' => 'Reading Assignment',
                'description' => 'Complete this reading test by the due date'
            ]);

        // Assert: API response is successful
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Test assigned to all students in ' . $class->name,
                'data' => [
                    'test_id' => $test->id,
                    'test_title' => $test->title,
                    'assigned_to_students' => 2 // Only active students
                ]
            ]);

        // Assert: Assignment was created with correct attributes
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals($class->id, $assignment->class_id);
        $this->assertEquals($test->id, $assignment->test_id);
        $this->assertEquals('Reading Assignment', $assignment->title);
        $this->assertEquals('Complete this reading test by the due date', $assignment->description);
        $this->assertEquals('auto_test', $assignment->source_type);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertTrue($assignment->is_published);

        // Assert: Student assignments were created for active students only
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(2, $studentAssignments);
        
        // Verify each student assignment
        $assignedStudentIds = $studentAssignments->pluck('student_id')->map(function($id) {
            return (string) $id;
        })->toArray();
        $this->assertContains((string) $student1->id, $assignedStudentIds);
        $this->assertContains((string) $student2->id, $assignedStudentIds);
        $this->assertNotContains((string) $student3->id, $assignedStudentIds); // Inactive student

        // Assert: All student assignments have correct initial status
        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals('not_started', $studentAssignment->status);
            $this->assertEquals(1, $studentAssignment->attempt_number);
            $this->assertEquals($assignment->id, $studentAssignment->assignment_id);
        }

        // Assert: Assignment relationships work correctly
        $this->assertInstanceOf(Test::class, $assignment->test);
        $this->assertInstanceOf(Classes::class, $assignment->class);
        $this->assertEquals($test->id, $assignment->test->id);
        $this->assertEquals($class->id, $assignment->class->id);
    }

    public function test_assignment_workflow_with_different_test_types()
    {
        // Test that different test types create assignments with correct types
        $testTypes = ['reading', 'writing', 'listening', 'speaking'];
        $expectedAssignmentTypes = ['reading_task', 'writing_task', 'listening_task', 'speaking_task'];

        foreach ($testTypes as $index => $testType) {
            // Arrange
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            $student = User::factory()->create(['role' => 'student']);
            
            ClassEnrollment::create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active'
            ]);
            
            $test = Test::factory()->create([
                'creator_id' => $teacher->id,
                'class_id' => $class->id,
                'is_published' => true,
                'type' => $testType,
                'title' => ucfirst($testType) . ' Test'
            ]);

            // Act
            $response = $this->actingAs($teacher)
                ->postJson("/api/v1/classes/{$class->id}/tests/{$test->id}/assign", [
                    'due_date' => now()->addDays(7)->toISOString(),
                    'title' => ucfirst($testType) . ' Assignment'
                ]);

            // Assert
            $response->assertStatus(200);
            
            $assignment = Assignment::where('test_id', $test->id)->first();
            $this->assertEquals($expectedAssignmentTypes[$index], $assignment->type);
            $this->assertEquals(ucfirst($testType) . ' Assignment', $assignment->title);
        }
    }
}