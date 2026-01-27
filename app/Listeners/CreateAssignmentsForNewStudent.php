<?php

namespace App\Listeners;

use App\Events\StudentEnrolledInClass;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener that creates assignments for newly enrolled students
 * Handles assignment creation for students joining classes with existing assignments
 */
class CreateAssignmentsForNewStudent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param StudentEnrolledInClass $event
     * @return void
     */
    public function handle(StudentEnrolledInClass $event): void
    {
        try {
            DB::beginTransaction();

            // Get all active assignments for the class
            $assignments = Assignment::where('class_id', $event->class->id)
                ->where('is_published', true)
                ->get();

            $createdCount = 0;

            foreach ($assignments as $assignment) {
                // Check if student already has this assignment
                $existingAssignment = StudentAssignment::where([
                    'assignment_id' => $assignment->id,
                    'student_id' => $event->student->id
                ])->exists();

                if (!$existingAssignment) {
                    $this->createStudentAssignment($assignment, $event->student);
                    $createdCount++;
                }
            }

            DB::commit();

            Log::info('Assignments created for newly enrolled student', [
                'student_id' => $event->student->id,
                'class_id' => $event->class->id,
                'assignments_created' => $createdCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create assignments for newly enrolled student', [
                'student_id' => $event->student->id,
                'class_id' => $event->class->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to allow for proper error handling
            throw $e;
        }
    }

    /**
     * Create a student assignment for the newly enrolled student
     */
    private function createStudentAssignment(Assignment $assignment, $student): StudentAssignment
    {
        return StudentAssignment::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'test_id' => $assignment->test_id,
            'assignment_type' => $assignment->type,
            'status' => 'not_started',
            'assigned_at' => now(),
            'attempt_number' => 1,
            'attempt_count' => 0,
            'time_spent_minutes' => 0
        ]);
    }
}