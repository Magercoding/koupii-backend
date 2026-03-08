<?php

namespace App\Services\V1\Assignment;

use App\Contracts\AssignmentFactoryInterface;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Factory for standardized assignment creation from tests and tasks.
 */
class AssignmentFactory implements AssignmentFactoryInterface
{
    /**
     * Create an assignment from a test with optional configuration.
     */
    public function createFromTest(Test $test, array $options = []): Assignment
    {
        if (!$test->canBeAutoAssigned()) {
            throw new \Exception("Test '{$test->title}' cannot be auto-assigned. Test must be published and assigned to a class.");
        }

        $assignmentData = [
            'class_id' => $test->class_id,
            'test_id' => $test->id,
            'assigned_by' => $test->creator_id,
            'title' => $options['title'] ?? $test->getAssignmentTitle(),
            'description' => $options['description'] ?? $test->description,
            'due_date' => $options['due_date'] ?? $this->getDefaultDueDate(),
            'close_date' => $options['close_date'] ?? null,
            'is_published' => $options['is_published'] ?? true,
            'max_attempts' => $options['max_attempts'] ?? 3,
            'instructions' => $options['instructions'] ?? null,
            'status' => 'active',
            'source_type' => 'auto_test',
            'source_id' => $test->id,
            'assignment_settings' => $options['settings'] ?? null,
            'auto_created_at' => now(),
            'type' => $this->getAssignmentType($test->type),
        ];

        try {
            $assignment = Assignment::create($assignmentData);

            Log::info('Assignment created from test', [
                'assignment_id' => $assignment->id,
                'test_id' => $test->id,
                'class_id' => $test->class_id,
            ]);

            return $assignment;
        } catch (\Exception $e) {
            Log::error('Failed to create assignment from test', [
                'test_id' => $test->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to create assignment from test: {$e->getMessage()}");
        }
    }

    /**
     * Create student assignments for all enrolled students in the assignment's class.
     */
    public function createStudentAssignments(Assignment $assignment): int
    {
        try {
            $enrolledStudents = ClassEnrollment::where('class_id', $assignment->class_id)
                ->where('status', 'active')
                ->pluck('student_id');

            if ($enrolledStudents->isEmpty()) {
                Log::info('No enrolled students found for assignment', [
                    'assignment_id' => $assignment->id,
                    'class_id' => $assignment->class_id,
                ]);
                return 0;
            }

            $now = now();
            $rows = $enrolledStudents->map(fn($studentId) => [
                'id' => (string) Str::uuid(),
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'assignment_type' => $assignment->type,
                'status' => StudentAssignment::STATUS_NOT_STARTED,
                'attempt_number' => 1,
                'attempt_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            StudentAssignment::insert($rows);

            Log::info('Student assignments created', [
                'assignment_id' => $assignment->id,
                'student_count' => count($rows),
                'class_id' => $assignment->class_id,
            ]);

            return count($rows);
        } catch (\Exception $e) {
            Log::error('Failed to create student assignments', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to create student assignments: {$e->getMessage()}");
        }
    }

    private function getDefaultDueDate(): Carbon
    {
        return now()->addDays(7);
    }

    private function getAssignmentType(string $testType): string
    {
        return match ($testType) {
            'reading' => 'reading_task',
            'writing' => 'writing_task',
            'listening' => 'listening_task',
            'speaking' => 'speaking_task',
            default => 'general_task',
        };
    }
}