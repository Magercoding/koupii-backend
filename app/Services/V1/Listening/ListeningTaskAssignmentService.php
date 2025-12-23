<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\ListeningTaskAssignment;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListeningTaskAssignmentService
{
    /**
     * Assign task to classrooms.
     */
    public function assignToClassrooms(ListeningTask $task, array $classroomIds): array
    {
        $assignments = [];

        DB::transaction(function () use ($task, $classroomIds, &$assignments) {
            // Remove existing assignments if reassigning
            ListeningTaskAssignment::where('listening_task_id', $task->id)->delete();

            foreach ($classroomIds as $classroomId) {
                $assignment = ListeningTaskAssignment::create([
                    'id' => Str::uuid(),
                    'listening_task_id' => $task->id,
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
    public function removeFromClassroom(ListeningTask $task, string $classroomId): bool
    {
        return DB::transaction(function () use ($task, $classroomId) {
            $deleted = ListeningTaskAssignment::where('listening_task_id', $task->id)
                ->where('classroom_id', $classroomId)
                ->delete();

            return $deleted > 0;
        });
    }

    /**
     * Get task assignments with details.
     */
    public function getTaskAssignments(ListeningTask $task): array
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
     * Get classroom's assigned tasks.
     */
    public function getClassroomTasks(string $classroomId): array
    {
        $classroom = Classes::with([
            'listeningTaskAssignments.listeningTask' => function ($query) {
                $query->where('is_published', true);
            }
        ])->findOrFail($classroomId);

        $tasks = $classroom->listeningTaskAssignments->map(function ($assignment) {
            $task = $assignment->listeningTask;
            
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'difficulty_level' => $task->difficulty_level,
                'task_type' => $task->task_type,
                'points' => $task->points,
                'time_limit' => $task->time_limit,
                'is_published' => $task->is_published,
                'assigned_at' => $assignment->assigned_at,
                'due_date' => $assignment->due_date,
                'creator' => $task->creator->name ?? 'Unknown',
            ];
        })->toArray();

        return $tasks;
    }

    /**
     * Bulk assign multiple tasks to multiple classrooms.
     */
    public function bulkAssign(array $taskIds, array $classroomIds): array
    {
        $results = [];

        DB::transaction(function () use ($taskIds, $classroomIds, &$results) {
            foreach ($taskIds as $taskId) {
                $task = ListeningTask::find($taskId);
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

    /**
     * Check if task is assigned to classroom.
     */
    public function isTaskAssignedToClassroom(ListeningTask $task, string $classroomId): bool
    {
        return ListeningTaskAssignment::where('listening_task_id', $task->id)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    /**
     * Get assignment by task and classroom.
     */
    public function getAssignment(ListeningTask $task, string $classroomId): ?ListeningTaskAssignment
    {
        return ListeningTaskAssignment::where('listening_task_id', $task->id)
            ->where('classroom_id', $classroomId)
            ->with(['classroom', 'assignedBy'])
            ->first();
    }
}