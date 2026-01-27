<?php

namespace App\Services\V1\Assignment;

use App\Contracts\AssignmentFactoryInterface;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Factory for standardized assignment creation across different test types
 * Handles automatic assignment creation and batch student assignment generation
 */
class AssignmentFactory implements AssignmentFactoryInterface
{
    /**
     * Create an assignment from a test with optional configuration
     *
     * @param Test $test The test to create an assignment from
     * @param array $options Optional configuration (due_date, instructions, etc.)
     * @return Assignment The created assignment
     * @throws \Exception If assignment creation fails
     */
    public function createFromTest(Test $test, array $options = []): Assignment
    {
        // Validate that the test can be auto-assigned
        if (!$test->canBeAutoAssigned()) {
            throw new \Exception("Test '{$test->title}' cannot be auto-assigned. Test must be published and assigned to a class.");
        }

        // Prepare assignment data with defaults
        $assignmentData = [
            'class_id' => $test->class_id,
            'test_id' => $test->id,
            'title' => $options['title'] ?? $test->getAssignmentTitle(),
            'description' => $options['description'] ?? $test->description,
            'due_date' => $options['due_date'] ?? $this->getDefaultDueDate(),
            'close_date' => $options['close_date'] ?? null,
            'is_published' => $options['is_published'] ?? true,
            'source_type' => 'auto_test',
            'source_id' => $test->id,
            'assignment_settings' => $options['settings'] ?? null,
            'auto_created_at' => now(),
            'type' => $this->getAssignmentType($test->type)
        ];

        try {
            $assignment = Assignment::create($assignmentData);
            
            Log::info('Assignment created from test', [
                'assignment_id' => $assignment->id,
                'test_id' => $test->id,
                'test_type' => $test->type,
                'assignment_type' => $assignment->type,
                'class_id' => $test->class_id
            ]);

            return $assignment;
            
        } catch (\Exception $e) {
            Log::error('Failed to create assignment from test', [
                'test_id' => $test->id,
                'test_type' => $test->type,
                'class_id' => $test->class_id,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception("Failed to create assignment from test: {$e->getMessage()}");
        }
    }

    /**
     * Create student assignments for all enrolled students in the assignment's class
     *
     * @param Assignment $assignment The assignment to create student assignments for
     * @return int The number of student assignments created
     * @throws \Exception If student assignment creation fails
     */
    public function createStudentAssignments(Assignment $assignment): int
    {
        try {
            // Get all actively enrolled students in the class
            $enrolledStudents = ClassEnrollment::where('class_id', $assignment->class_id)
                ->where('status', 'active')
                ->pluck('student_id');

            if ($enrolledStudents->isEmpty()) {
                Log::info('No enrolled students found for assignment', [
                    'assignment_id' => $assignment->id,
                    'class_id' => $assignment->class_id
                ]);
                return 0;
            }

            // Prepare student assignment data for batch insert
            $studentAssignments = [];
            $now = now();

            foreach ($enrolledStudents as $studentId) {
                $studentAssignments[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'assignment_id' => $assignment->id,
                    'student_id' => $studentId,
                    'assignment_type' => $assignment->type,
                    'status' => StudentAssignment::STATUS_NOT_STARTED,
                    'attempt_number' => 1,
                    'attempt_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // Batch insert for performance
            StudentAssignment::insert($studentAssignments);

            $studentCount = count($studentAssignments);
            
            Log::info('Student assignments created', [
                'assignment_id' => $assignment->id,
                'student_count' => $studentCount,
                'class_id' => $assignment->class_id
            ]);

            return $studentCount;
            
        } catch (\Exception $e) {
            Log::error('Failed to create student assignments', [
                'assignment_id' => $assignment->id,
                'class_id' => $assignment->class_id,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception("Failed to create student assignments: {$e->getMessage()}");
        }
    }

    /**
     * Get default due date (7 days from now)
     */
    private function getDefaultDueDate(): Carbon
    {
        return now()->addDays(7);
    }

    /**
     * Map test type to assignment type
     * Handles different test types (reading, writing, listening, speaking)
     */
    private function getAssignmentType(string $testType): string
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