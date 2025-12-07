<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingTaskAssignmentService
{
    /**
     * Assign task to classrooms.
     */
    public function assignToClassrooms(WritingTask $task, array $classroomIds): array
    {
        $assignments = [];

        DB::transaction(function () use ($task, $classroomIds, &$assignments) {
            // Remove existing assignments if reassigning
            WritingTaskAssignment::where('writing_task_id', $task->id)->delete();

            foreach ($classroomIds as $classroomId) {
                $assignment = WritingTaskAssignment::create([
                    'id' => Str::uuid(),
                    'writing_task_id' => $task->id,
                    'classroom_id' => $classroomId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);

                $assignments[] = $assignment;
            }

            // Publish the task when assigned
            $task->update(['is_published' => true]);
        });

        return $assignments;
    }

    /**
     * Remove task from classroom.
     */
    public function removeFromClassroom(WritingTask $task, string $classroomId): bool
    {
        return DB::transaction(function () use ($task, $classroomId) {
            $deleted = WritingTaskAssignment::where('writing_task_id', $task->id)
                ->where('classroom_id', $classroomId)
                ->delete();

            return $deleted > 0;
        });
    }

    /**
     * Get task assignments with details.
     */
    public function getTaskAssignments(WritingTask $task): array
    {
        return $task->assignments()->with(['classroom.students'])->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'classroom_id' => $assignment->classroom->id,
                    'classroom_name' => $assignment->classroom->name,
                    'student_count' => $assignment->classroom->students->count(),
                    'assigned_at' => $assignment->assigned_at,
                    'assigned_by' => $assignment->assignedBy->name,
                ];
            })->toArray();
    }

    /**
     * Bulk assign multiple tasks to multiple classrooms.
     */
    public function bulkAssign(array $taskIds, array $classroomIds): array
    {
        $results = [];

        DB::transaction(function () use ($taskIds, $classroomIds, &$results) {
            foreach ($taskIds as $taskId) {
                $task = WritingTask::find($taskId);
                if ($task) {
                    $assignments = $this->assignToClassrooms($task, $classroomIds);
                    $results[$taskId] = [
                        'success' => true,
                        'assignments_count' => count($assignments),
                    ];
                } else {
                    $results[$taskId] = [
                        'success' => false,
                        'error' => 'Task not found',
                    ];
                }
            }
        });

        return $results;
    }
}