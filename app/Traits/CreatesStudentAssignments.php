<?php

namespace App\Traits;

use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use Illuminate\Support\Str;

/**
 * Shared helper for creating StudentAssignment rows after an Assignment is persisted.
 * Used by all task-creation services (Listening, Speaking, Writing, Reading).
 */
trait CreatesStudentAssignments
{
    /**
     * Bulk-insert a StudentAssignment row for every active student enrolled in the class.
     * Mirrors AssignmentService::createStudentAssignments().
     */
    protected function createStudentAssignmentsForAssignment(Assignment $assignment): int
    {
        $studentIds = ClassEnrollment::where('class_id', $assignment->class_id)
            ->where('status', 'active')
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now  = now();
        $rows = $studentIds->map(fn ($studentId) => [
            'id'              => (string) Str::uuid(),
            'assignment_id'   => $assignment->id,
            'student_id'      => $studentId,
            'assignment_type' => $assignment->type,
            'status'          => StudentAssignment::STATUS_NOT_STARTED,
            'attempt_number'  => 1,
            'attempt_count'   => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        StudentAssignment::insert($rows);

        return count($rows);
    }
}
