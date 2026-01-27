<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\V1\Assignment\AssignmentFactory;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Eris\TestTrait;
use Eris\Generators;

/**
 * Focused property-based test for the core automatic assignment creation property
 * 
 * This test specifically validates Property 1 from the design document:
 * "For any teacher, test, and class, when the teacher adds the test to the class, 
 * the system should automatically create exactly one Class_Assignment record"
 */
class AssignmentAutomaticCreationCorePropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private AssignmentFactory $assignmentFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentFactory = new AssignmentFactory();
    }

    /**
     * Property 1: Automatic Assignment Creation
     * For any teacher, test, and class, when the teacher adds the test to the class, 
     * the system should automatically create exactly one Class_Assignment record
     * 
     * **Validates: Requirements 1.1**
     */
    public function test_core_property_automatic_assignment_creation()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking'])
        )->then(function ($testType) {
            // Arrange: Create teacher, class, and test
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => "Test for {$testType}",
                'is_published' => true
            ]);

            // Act: Create assignment from test (simulating when teacher adds test to class)
            $assignment = $this->assignmentFactory->createFromTest($test);

            // Assert: Verify exactly one Class_Assignment record was created
            $this->assertInstanceOf(Assignment::class, $assignment, 
                "Assignment creation should return an Assignment instance");
            
            // Verify it's exactly one assignment for this test-class combination
            $assignmentCount = Assignment::where('test_id', $test->id)
                ->where('class_id', $class->id)
                ->count();
            $this->assertEquals(1, $assignmentCount, 
                "Expected exactly 1 assignment for test type '{$testType}', but found {$assignmentCount}");
            
            // Verify the assignment is properly linked to the test and class
            $this->assertEquals($test->id, $assignment->test_id, 
                "Assignment should be linked to the correct test");
            $this->assertEquals($class->id, $assignment->class_id, 
                "Assignment should be linked to the correct class");
            
            // Verify it's marked as auto-created
            $this->assertEquals('auto_test', $assignment->source_type, 
                "Assignment should be marked as automatically created");
            $this->assertEquals($test->id, $assignment->source_id, 
                "Assignment should reference the source test");
            $this->assertNotNull($assignment->auto_created_at, 
                "Assignment should have auto_created_at timestamp");
        });
    }
}