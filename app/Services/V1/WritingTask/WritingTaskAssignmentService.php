<?php

namespace App\Services\V1\WritingTask;

use App\Models\Assignment;
use App\Models\WritingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingTaskAssignmentService
{
    public function assignToClassrooms(WritingTask $task, array $classroomIds): array
    {
        $assignments = [];

        DB::transaction(function () use ($task, $classroomIds, &$assignments) {
            Assignment::where('task_id', $task->id)->where('task_type', 'writing_task')->delete();

            foreach ($classroomIds as $classroomId) {
                $assignment = Assignment::create([
                    'id'          => Str::uuid(),
                    'class_id'    => $classroomId,
                    'task_id'     => $task->id,
                    'task_type'   => 'writing_task',
                    'assigned_by' => Auth::id(),
                    'title'       => $task->title . ' - Assignment',
                    'is_published'=> true,
                    'status'      => 'active',
                    'source_type' => 'manual',
                    'type'        => 'writing',
                    'max_attempts'=> 3,
                ]);
                $assignments[] = $assignment;
            }

            $task->update(['is_published' => true]);
        });

        return $assignments;
    }

    public function removeFromClassroom(WritingTask $task, string $classroomId): bool
    {
        return DB::transaction(function () use ($task, $classroomId) {
            return Assignment::where('task_id', $task->id)
                ->where('task_type', 'writing_task')
                ->where('class_id', $classroomId)
                ->delete() > 0;
        });
    }

    public function getTaskAssignments(WritingTask $task): array
    {
        return Assignment::where('task_id', $task->id)
            ->where('task_type', 'writing_task')
            ->with(['class'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id'             => $assignment->id,
                    'classroom_id'   => $assignment->class?->id,
                    'classroom_name' => $assignment->class?->name,
                    'assigned_at'    => $assignment->created_at,
                ];
            })->toArray();
    }

    public function bulkAssign(array $taskIds, array $classroomIds): array
    {
        $results = [];

        DB::transaction(function () use ($taskIds, $classroomIds, &$results) {
            foreach ($taskIds as $taskId) {
                $task = WritingTask::find($taskId);
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
}
