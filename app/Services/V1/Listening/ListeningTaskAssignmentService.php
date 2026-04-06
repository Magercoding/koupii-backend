<?php

namespace App\Services\V1\Listening;

use App\Models\Assignment;
use App\Models\ListeningTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListeningTaskAssignmentService
{
    public function assignToClassrooms(ListeningTask $task, array $classroomIds): array
    {
        $assignments = [];

        DB::transaction(function () use ($task, $classroomIds, &$assignments) {
            Assignment::where('task_id', $task->id)->where('task_type', 'listening_task')->delete();

            foreach ($classroomIds as $classroomId) {
                $assignment = Assignment::create([
                    'id'          => Str::uuid(),
                    'class_id'    => $classroomId,
                    'task_id'     => $task->id,
                    'task_type'   => 'listening_task',
                    'assigned_by' => Auth::id(),
                    'title'       => $task->title,
                    'is_published'=> true,
                    'status'      => 'active',
                    'source_type' => 'manual',
                    'type'        => 'listening',
                    'max_attempts'=> 3,
                ]);
                $assignments[] = $assignment;
            }

            $task->update(['is_published' => true]);
        });

        return $assignments;
    }

    public function removeFromClassroom(ListeningTask $task, string $classroomId): bool
    {
        return DB::transaction(function () use ($task, $classroomId) {
            return Assignment::where('task_id', $task->id)
                ->where('task_type', 'listening_task')
                ->where('class_id', $classroomId)
                ->delete() > 0;
        });
    }

    public function getTaskAssignments(ListeningTask $task): array
    {
        return Assignment::where('task_id', $task->id)
            ->where('task_type', 'listening_task')
            ->with(['class'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id'             => $assignment->id,
                    'classroom_id'   => $assignment->class?->id,
                    'classroom_name' => $assignment->class?->name,
                    'assigned_at'    => $assignment->created_at,
                    'due_date'       => $assignment->due_date,
                ];
            })->toArray();
    }

    public function getClassroomTasks(string $classroomId): array
    {
        return Assignment::where('class_id', $classroomId)
            ->where('task_type', 'listening_task')
            ->get()
            ->map(function ($assignment) {
                $task = $assignment->getTask();
                return [
                    'id'             => $task?->id,
                    'title'          => $task?->title,
                    'description'    => $task?->description,
                    'difficulty_level'=> $task?->difficulty_level,
                    'is_published'   => $task?->is_published,
                    'assigned_at'    => $assignment->created_at,
                    'due_date'       => $assignment->due_date,
                ];
            })->toArray();
    }

    public function bulkAssign(array $taskIds, array $classroomIds): array
    {
        $results = [];

        DB::transaction(function () use ($taskIds, $classroomIds, &$results) {
            foreach ($taskIds as $taskId) {
                $task = ListeningTask::find($taskId);
                if ($task) {
                    $assignments = $this->assignToClassrooms($task, $classroomIds);
                    $results[$taskId] = ['success' => true, 'assignments_count' => count($assignments)];
                } else {
                    $results[$taskId] = ['success' => false, 'error' => 'Task not found'];
                }
            }
        });

        return $results;
    }

    public function isTaskAssignedToClassroom(ListeningTask $task, string $classroomId): bool
    {
        return Assignment::where('task_id', $task->id)
            ->where('task_type', 'listening_task')
            ->where('class_id', $classroomId)
            ->exists();
    }

    public function getAssignment(ListeningTask $task, string $classroomId): ?Assignment
    {
        return Assignment::where('task_id', $task->id)
            ->where('task_type', 'listening_task')
            ->where('class_id', $classroomId)
            ->with(['class', 'assignedBy'])
            ->first();
    }
}
