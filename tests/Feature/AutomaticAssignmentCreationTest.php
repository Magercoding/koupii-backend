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

class AutomaticAssignmentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_assignment_creation_when_test_is_published_with_class()
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

        // Act: Create a published test with class_id
        $response = $this->actingAs($teacher)
            ->postJson("/api/v1/classes/{$class->id}/tests", [
                'title' => 'Auto Assignment Test',
                'description' => 'This should create automatic assignments',
                'type' => 'reading',
                'difficulty' => 'beginner',
                'class_id' => $class->id,
                'is_published' => true,
                'passages' => [
                    [
                        'title' => 'Test Passage',
                        'description' => 'Test passage content',
                        'question_groups' => [
                            [
                                'instruction' => 'Answer the questions',
                                'questions' => [
                                    [
                                        'question_type' => 'choose_correct_answer',
                                        'question_number' => 1,
                                        'question_text' => 'What is this test about?',
                                        'points_value' => 100,
                                        'correct_answers' => ['A'],
                                        'options' => [
                                            ['option_key' => 'A', 'option_text' => 'Testing'],
                                            ['option_key' => 'B', 'option_text' => 'Not Testing']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        // Assert: Test was created successfully
        $response->assertStatus(201);
        $testData = $response->json('data');
        
        // Assert: Assignment was automatically created
        $assignment = Assignment::where('test_id', $testData['id'])->first();
        $this->assertNotNull($assignment, 'Assignment should be automatically created');
        $this->assertEquals($class->id, $assignment->class_id);
        $this->assertEquals('reading_task', $assignment->type);
        $this->assertEquals('auto_test', $assignment->source_type);
        
        // Assert: Student assignments were created
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
        $this->assertCount(2, $studentAssignments, 'Should create assignments for both students');
        
        foreach ($studentAssignments as $studentAssignment) {
            $this->assertEquals('not_started', $studentAssignment->status);
            $this->assertEquals('reading_task', $studentAssignment->assignment_type);
        }
    }

    public function test_no_automatic_assignment_when_test_is_not_published()
    {
        // Arrange: Create teacher and class
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        // Act: Create an unpublished test with class_id
        $response = $this->actingAs($teacher)
            ->postJson("/api/v1/classes/{$class->id}/tests", [
                'title' => 'Unpublished Test',
                'description' => 'This should NOT create automatic assignments',
                'type' => 'reading',
                'difficulty' => 'beginner',
                'class_id' => $class->id,
                'is_published' => false
            ]);

        // Assert: Test was created successfully
        $response->assertStatus(201);
        $testData = $response->json('data');
        
        // Assert: No assignment was created
        $assignment = Assignment::where('test_id', $testData['id'])->first();
        $this->assertNull($assignment, 'Assignment should NOT be created for unpublished test');
    }

    public function test_automatic_assignment_when_test_is_published_later()
    {
        // Arrange: Create teacher, class, and student
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
        $student = User::factory()->create(['role' => 'student']);
        
        ClassEnrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'active'
        ]);

        // Create unpublished test first
        $test = Test::factory()->create([
            'creator_id' => $teacher->id,
            'class_id' => $class->id,
            'is_published' => false,
            'type' => 'reading'
        ]);

        // Verify no assignment exists yet
        $this->assertCount(0, Assignment::where('test_id', $test->id)->get());

        // Act: Publish the test
        $response = $this->actingAs($teacher)
            ->putJson("/api/v1/classes/{$class->id}/tests/{$test->id}", [
                'title' => $test->title,
                'description' => $test->description,
                'type' => $test->type,
                'difficulty' => $test->difficulty,
                'class_id' => $class->id,
                'is_published' => true
            ]);

        // Assert: Test was updated successfully
        $response->assertStatus(200);
        
        // Assert: Assignment was automatically created
        $assignment = Assignment::where('test_id', $test->id)->first();
        $this->assertNotNull($assignment, 'Assignment should be created when test is published');
        
        // Assert: Student assignment was created
        $studentAssignment = StudentAssignment::where('assignment_id', $assignment->id)->first();
        $this->assertNotNull($studentAssignment, 'Student assignment should be created');
    }
}