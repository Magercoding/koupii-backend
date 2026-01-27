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
 * Property-based test for test type preservation during assignment creation
 * 
 * This test specifically validates Property 5 from the design document:
 * "For any test of any type (reading, writing, listening, speaking) assigned to a class, 
 * the created assignment should preserve the same type"
 */
class AssignmentTestTypePreservationPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private AssignmentFactory $assignmentFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentFactory = new AssignmentFactory();
    }

    /**
     * Property 5: Test Type Preservation
     * For any test of any type (reading, writing, listening, speaking) assigned to a class, 
     * the created assignment should preserve the same type
     * 
     * **Validates: Requirements 1.5, 4.1, 4.2, 4.3, 4.4**
     */
    public function test_property_test_type_preservation()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking'])
        )->then(function ($testType) {
            // Arrange: Create teacher, class, and test of specific type
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => "Test for {$testType}",
                'is_published' => true
            ]);

            // Act: Create assignment from test
            $assignment = $this->assignmentFactory->createFromTest($test);

            // Assert: Verify type preservation
            $expectedAssignmentType = $this->getExpectedAssignmentType($testType);
            $this->assertEquals($expectedAssignmentType, $assignment->type, 
                "Assignment type should be '{$expectedAssignmentType}' for test type '{$testType}'");
            
            // Verify the original test type is preserved in the relationship
            $this->assertEquals($testType, $test->type, 
                "Original test type should remain '{$testType}'");
            
            // Verify the assignment is properly linked to the test
            $this->assertEquals($test->id, $assignment->test_id, 
                "Assignment should be linked to the correct test");
            
            // Verify the test's getAssignmentType method returns the same type as the assignment
            $this->assertEquals($test->getAssignmentType(), $assignment->type,
                "Test's getAssignmentType() should match the created assignment type");
        });
    }

    /**
     * Property test for type preservation with various test configurations
     * Verifies that type preservation works regardless of other test properties
     */
    public function test_property_type_preservation_with_various_configurations()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::string(),
            Generators::string(),
            Generators::bool(),
            Generators::bool()
        )->then(function ($testType, $testTitle, $testDescription, $allowRepetition, $isPublished) {
            // Filter out empty or very short strings
            if (strlen($testTitle) < 3 || strlen($testDescription) < 5) {
                return; // Skip this iteration
            }
            
            // Truncate strings if they're too long
            $testTitle = substr($testTitle, 0, 50);
            $testDescription = substr($testDescription, 0, 100);
            
            // Only test with published tests (unpublished tests can't be auto-assigned)
            if (!$isPublished) {
                return;
            }
            
            // Arrange: Create teacher, class, and test with various configurations
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => $testTitle,
                'description' => $testDescription,
                'is_published' => $isPublished,
                'allow_repetition' => $allowRepetition
            ]);

            // Act: Create assignment from test
            $assignment = $this->assignmentFactory->createFromTest($test);

            // Assert: Verify type preservation regardless of other properties
            $expectedAssignmentType = $this->getExpectedAssignmentType($testType);
            $this->assertEquals($expectedAssignmentType, $assignment->type, 
                "Assignment type should be '{$expectedAssignmentType}' for test type '{$testType}' " .
                "regardless of title='{$testTitle}', allow_repetition={$allowRepetition}");
            
            // Verify the mapping is consistent with the Test model's method
            $this->assertEquals($test->getAssignmentType(), $assignment->type,
                "Assignment type should match Test model's getAssignmentType() method");
        });
    }

    /**
     * Property test for type preservation with custom assignment options
     * Verifies that custom options don't interfere with type preservation
     */
    public function test_property_type_preservation_with_custom_options()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::string(),
            Generators::string(),
            Generators::bool()
        )->then(function ($testType, $customTitle, $customDescription, $customPublished) {
            // Filter out empty or very short strings
            if (strlen($customTitle) < 3 || strlen($customDescription) < 5) {
                return; // Skip this iteration
            }
            
            // Truncate strings if they're too long
            $customTitle = substr($customTitle, 0, 100);
            $customDescription = substr($customDescription, 0, 200);
            
            // Arrange: Create teacher, class, and test
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'is_published' => true
            ]);

            $customOptions = [
                'title' => $customTitle,
                'description' => $customDescription,
                'is_published' => $customPublished,
                'due_date' => now()->addDays(14),
                'settings' => ['custom' => 'value']
            ];

            // Act: Create assignment with custom options
            $assignment = $this->assignmentFactory->createFromTest($test, $customOptions);

            // Assert: Verify type preservation despite custom options
            $expectedAssignmentType = $this->getExpectedAssignmentType($testType);
            $this->assertEquals($expectedAssignmentType, $assignment->type, 
                "Assignment type should be '{$expectedAssignmentType}' for test type '{$testType}' " .
                "even with custom options");
            
            // Verify custom options were applied but type was preserved
            $this->assertEquals($customTitle, $assignment->title);
            $this->assertEquals($customDescription, $assignment->description);
            $this->assertEquals($customPublished, $assignment->is_published);
            $this->assertEquals(['custom' => 'value'], $assignment->assignment_settings);
            
            // Verify the type mapping is still correct
            $this->assertEquals($test->getAssignmentType(), $assignment->type,
                "Assignment type should match Test model's getAssignmentType() method even with custom options");
        });
    }

    /**
     * Property test for bidirectional type consistency
     * Verifies that both Test and AssignmentFactory use the same type mapping
     */
    public function test_property_bidirectional_type_consistency()
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
                'is_published' => true
            ]);

            // Act: Get assignment type from both sources
            $testModelAssignmentType = $test->getAssignmentType();
            $assignment = $this->assignmentFactory->createFromTest($test);
            $factoryAssignmentType = $assignment->type;

            // Assert: Verify both sources produce the same assignment type
            $this->assertEquals($testModelAssignmentType, $factoryAssignmentType,
                "Test model's getAssignmentType() and AssignmentFactory should produce the same type for '{$testType}'");
            
            // Verify the expected mapping
            $expectedType = $this->getExpectedAssignmentType($testType);
            $this->assertEquals($expectedType, $testModelAssignmentType,
                "Test model should return '{$expectedType}' for test type '{$testType}'");
            $this->assertEquals($expectedType, $factoryAssignmentType,
                "AssignmentFactory should create assignment with type '{$expectedType}' for test type '{$testType}'");
        });
    }

    /**
     * Property test for type preservation with edge cases
     * Verifies that type preservation works with unusual but valid test configurations
     */
    public function test_property_type_preservation_edge_cases()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::elements(['', '   ', 'a', 'very long title that exceeds normal length expectations for a test title']),
            Generators::elements([null, '', 'short', 'a very detailed description that provides comprehensive information about the test'])
        )->then(function ($testType, $edgeTitle, $edgeDescription) {
            // Handle edge case titles
            $testTitle = empty(trim($edgeTitle)) ? "Default {$testType} Test" : trim($edgeTitle);
            if (strlen($testTitle) > 100) {
                $testTitle = substr($testTitle, 0, 100);
            }
            
            // Handle edge case descriptions
            $testDescription = $edgeDescription ?? "Default description for {$testType} test";
            if (strlen($testDescription) > 200) {
                $testDescription = substr($testDescription, 0, 200);
            }
            
            // Arrange: Create teacher, class, and test with edge case data
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => $testTitle,
                'description' => $testDescription,
                'is_published' => true
            ]);

            // Act: Create assignment from test
            $assignment = $this->assignmentFactory->createFromTest($test);

            // Assert: Verify type preservation even with edge case data
            $expectedAssignmentType = $this->getExpectedAssignmentType($testType);
            $this->assertEquals($expectedAssignmentType, $assignment->type, 
                "Assignment type should be '{$expectedAssignmentType}' for test type '{$testType}' " .
                "even with edge case title='{$testTitle}' and description='{$testDescription}'");
            
            // Verify the test type itself wasn't affected by edge case data
            $this->assertEquals($testType, $test->type,
                "Test type should remain '{$testType}' regardless of edge case data");
        });
    }

    /**
     * Helper method to get expected assignment type for a given test type
     * This centralizes the type mapping logic for the test
     */
    private function getExpectedAssignmentType(string $testType): string
    {
        return match ($testType) {
            'reading' => 'reading_task',
            'writing' => 'writing_task',
            'listening' => 'listening_task',
            'speaking' => 'speaking_task',
            default => 'general_task'
        };
    }
}