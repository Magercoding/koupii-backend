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
use Eris\TestTrait;
use Eris\Generators;

/**
 * Property-based tests for automatic assignment creation
 * 
 * These tests verify universal properties that should hold across all valid inputs
 * using randomized testing with minimum 100 iterations per property.
 */
class AssignmentAutomaticCreationPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private AssignmentFactory $assignmentFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentFactory = new AssignmentFactory();
    }

    /**
     * Feature: class-assignment-automation, Property 1: Automatic Assignment Creation
     * For any teacher, test, and class, when the teacher adds the test to the class, 
     * the system should automatically create exactly one Class_Assignment record
     * 
     * **Validates: Requirements 1.1**
     */
    public function test_property_automatic_assignment_creation()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::string(),
            Generators::string(),
            Generators::bool()
        )->then(function ($testType, $testTitle, $testDescription, $allowRepetition) {
            // Filter out empty or very short strings
            if (strlen($testTitle) < 3 || strlen($testDescription) < 5) {
                return; // Skip this iteration
            }
            
            // Truncate strings if they're too long
            $testTitle = substr($testTitle, 0, 50);
            $testDescription = substr($testDescription, 0, 100);
            
            // Arrange: Create teacher, class, and test
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'title' => $testTitle,
                'description' => $testDescription,
                'is_published' => true,
                'allow_repetition' => $allowRepetition
            ]);

            // Act: Create assignment from test (simulating automatic assignment creation)
            $assignment = $this->assignmentFactory->createFromTest($test);

            // Assert: Verify exactly one assignment was created with correct properties
            $this->assertInstanceOf(Assignment::class, $assignment);
            $this->assertEquals($test->id, $assignment->source_id);
            $this->assertEquals('auto_test', $assignment->source_type);
            $this->assertEquals($test->id, $assignment->test_id);
            $this->assertEquals($class->id, $assignment->class_id);
            $this->assertEquals($testTitle, $assignment->title);
            $this->assertTrue($assignment->is_published);
            $this->assertNotNull($assignment->auto_created_at);
            
            // Verify assignment type mapping
            $expectedType = match ($testType) {
                'reading' => 'reading_task',
                'writing' => 'writing_task',
                'listening' => 'listening_task',
                'speaking' => 'speaking_task',
                default => 'general_task'
            };
            $this->assertEquals($expectedType, $assignment->type);

            // Verify only one assignment was created for this test
            $assignmentCount = Assignment::where('test_id', $test->id)
                ->where('class_id', $class->id)
                ->count();
            $this->assertEquals(1, $assignmentCount, 
                "Expected exactly 1 assignment, but found {$assignmentCount} for test type: {$testType}");
        });
    }

    /**
     * Property test for assignment creation with various student enrollment scenarios
     * Verifies that assignment creation works regardless of class enrollment status
     */
    public function test_property_assignment_creation_with_different_enrollment_scenarios()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::choose(0, 10), // Number of enrolled students
            Generators::choose(0, 5)   // Number of inactive students
        )->then(function ($testType, $activeStudentCount, $inactiveStudentCount) {
            // Arrange: Create teacher, class, and test
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'is_published' => true
            ]);

            // Create active students
            $activeStudents = User::factory()->count($activeStudentCount)->create(['role' => 'student']);
            foreach ($activeStudents as $student) {
                ClassEnrollment::factory()->create([
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'status' => 'active'
                ]);
            }

            // Create inactive students
            $inactiveStudents = User::factory()->count($inactiveStudentCount)->create(['role' => 'student']);
            foreach ($inactiveStudents as $student) {
                ClassEnrollment::factory()->create([
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'status' => 'inactive'
                ]);
            }

            // Act: Create assignment from test
            $assignment = $this->assignmentFactory->createFromTest($test);
            $studentAssignmentCount = $this->assignmentFactory->createStudentAssignments($assignment);

            // Assert: Verify assignment creation properties
            $this->assertInstanceOf(Assignment::class, $assignment);
            $this->assertEquals('auto_test', $assignment->source_type);
            
            // Verify student assignment count matches active enrollments only
            $this->assertEquals($activeStudentCount, $studentAssignmentCount,
                "Expected {$activeStudentCount} student assignments, but got {$studentAssignmentCount}");
            
            // Verify actual student assignments in database
            $actualStudentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->count();
            $this->assertEquals($activeStudentCount, $actualStudentAssignments,
                "Database contains {$actualStudentAssignments} student assignments, expected {$activeStudentCount}");
        });
    }

    /**
     * Property test for assignment creation with custom options
     * Verifies that custom options are properly applied during assignment creation
     */
    public function test_property_assignment_creation_with_custom_options()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::string(),
            Generators::string(),
            Generators::bool()
        )->then(function ($testType, $customTitle, $customDescription, $isPublished) {
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
                'is_published' => $isPublished,
                'due_date' => now()->addDays(14),
                'settings' => ['custom' => 'value']
            ];

            // Act: Create assignment with custom options
            $assignment = $this->assignmentFactory->createFromTest($test, $customOptions);

            // Assert: Verify custom options are applied
            $this->assertEquals($customTitle, $assignment->title);
            $this->assertEquals($customDescription, $assignment->description);
            $this->assertEquals($isPublished, $assignment->is_published);
            $this->assertEquals(['custom' => 'value'], $assignment->assignment_settings);
            $this->assertNotNull($assignment->due_date);
            
            // Verify core assignment properties remain correct
            $this->assertEquals('auto_test', $assignment->source_type);
            $this->assertEquals($test->id, $assignment->source_id);
            $this->assertEquals($test->id, $assignment->test_id);
            $this->assertEquals($class->id, $assignment->class_id);
        });
    }

    /**
     * Property test for assignment creation failure scenarios
     * Verifies that invalid tests cannot be auto-assigned
     */
    public function test_property_assignment_creation_failure_scenarios()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::bool(), // is_published
            Generators::bool()  // has_class_id
        )->then(function ($testType, $isPublished, $hasClassId) {
            // Skip valid scenarios (we test those in other properties)
            if ($isPublished && $hasClassId) {
                return;
            }

            // Arrange: Create teacher and potentially a class
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = $hasClassId ? Classes::factory()->create(['teacher_id' => $teacher->id]) : null;
            
            $test = Test::factory()->create([
                'class_id' => $hasClassId ? $class->id : null,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'is_published' => $isPublished
            ]);

            // Act & Assert: Verify that invalid tests throw exceptions
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('cannot be auto-assigned');
            
            $this->assignmentFactory->createFromTest($test);
        });
    }

    /**
     * Property test for database consistency during assignment creation
     * Verifies that all database operations maintain referential integrity
     */
    public function test_property_database_consistency_during_assignment_creation()
    {
        $this->forAll(
            Generators::elements(['reading', 'writing', 'listening', 'speaking']),
            Generators::choose(1, 5) // Number of students
        )->then(function ($testType, $studentCount) {
            // Arrange: Create teacher, class, test, and students
            $teacher = User::factory()->create(['role' => 'teacher']);
            $class = Classes::factory()->create(['teacher_id' => $teacher->id]);
            
            $test = Test::factory()->create([
                'class_id' => $class->id,
                'creator_id' => $teacher->id,
                'type' => $testType,
                'is_published' => true
            ]);

            $students = User::factory()->count($studentCount)->create(['role' => 'student']);
            foreach ($students as $student) {
                ClassEnrollment::factory()->create([
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'status' => 'active'
                ]);
            }

            // Act: Create assignment and student assignments
            $assignment = $this->assignmentFactory->createFromTest($test);
            $createdCount = $this->assignmentFactory->createStudentAssignments($assignment);

            // Assert: Verify database consistency
            // 1. Assignment exists and has correct foreign keys
            $this->assertTrue(Assignment::where('id', $assignment->id)->exists());
            $this->assertTrue(Test::where('id', $assignment->test_id)->exists());
            $this->assertTrue(Classes::where('id', $assignment->class_id)->exists());
            
            // 2. Student assignments exist and have correct foreign keys
            $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)->get();
            $this->assertCount($studentCount, $studentAssignments);
            
            foreach ($studentAssignments as $studentAssignment) {
                $this->assertTrue(User::where('id', $studentAssignment->student_id)->exists());
                $this->assertEquals($assignment->id, $studentAssignment->assignment_id);
                $this->assertEquals($assignment->type, $studentAssignment->assignment_type);
                $this->assertEquals(StudentAssignment::STATUS_NOT_STARTED, $studentAssignment->status);
            }
            
            // 3. Verify no orphaned records
            $orphanedAssignments = Assignment::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('tests')
                      ->whereRaw('tests.id = assignments.test_id');
            })->count();
            $this->assertEquals(0, $orphanedAssignments);
            
            $orphanedStudentAssignments = StudentAssignment::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('assignments')
                      ->whereRaw('assignments.id = student_assignments.assignment_id');
            })->count();
            $this->assertEquals(0, $orphanedStudentAssignments);
        });
    }
}