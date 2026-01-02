<?php

namespace App\Http\Controllers\V1\ReadingTask;

use App\Http\Controllers\Controller;
use App\Models\ReadingTask;
use App\Models\ReadingTaskAssignment;
use App\Models\Classes;
use App\Http\Resources\V1\ReadingTask\ReadingTaskAssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ReadingTaskAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('role:admin,teacher', except: ['getAssignments']),
        ];
    }

    /**
     * Get assignments for a reading task.
     */
    public function getAssignments(Request $request, string $id)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role === 'student') {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $assignments = ReadingTaskAssignment::with(['classroom', 'assignedBy'])
            ->where('reading_task_id', $id)
            ->get();

        return ReadingTaskAssignmentResource::collection($assignments);
    }

    /**
     * Assign task to classrooms.
     */
    public function assignToClassrooms(Request $request, string $id)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classes,id',
            'due_date' => 'nullable|date|after:now',
        ]);

        $assignments = [];
        foreach ($request->classroom_ids as $classroomId) {
            // Check if already assigned
            $existingAssignment = ReadingTaskAssignment::where('reading_task_id', $id)
                ->where('classroom_id', $classroomId)
                ->first();

            if (!$existingAssignment) {
                $assignment = ReadingTaskAssignment::create([
                    'reading_task_id' => $id,
                    'classroom_id' => $classroomId,
                    'assigned_by' => $user->id,
                    'due_date' => $request->due_date,
                    'assigned_at' => now(),
                ]);

                $assignments[] = $assignment->load(['classroom', 'assignedBy']);
            }
        }

        return response()->json([
            'message' => 'Task assigned successfully',
            'assignments' => ReadingTaskAssignmentResource::collection(collect($assignments))
        ]);
    }

    /**
     * Remove task assignment from classroom.
     */
    public function removeFromClassroom(Request $request, string $id, string $classroomId)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $assignment = ReadingTaskAssignment::where('reading_task_id', $id)
            ->where('classroom_id', $classroomId)
            ->firstOrFail();

        $assignment->delete();

        return response()->json(['message' => 'Assignment removed successfully']);
    }

    /**
     * Bulk assign tasks to classrooms.
     */
    public function bulkAssign(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:reading_tasks,id',
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classes,id',
            'due_date' => 'nullable|date|after:now',
        ]);

        $assignments = [];
        $errors = [];

        foreach ($request->task_ids as $taskId) {
            $task = ReadingTask::find($taskId);

            // Check permissions for each task
            if ($user->role === 'teacher' && $task->created_by !== $user->id) {
                $errors[] = "Access denied for task: {$task->title}";
                continue;
            }

            foreach ($request->classroom_ids as $classroomId) {
                // Check if already assigned
                $existingAssignment = ReadingTaskAssignment::where('reading_task_id', $taskId)
                    ->where('classroom_id', $classroomId)
                    ->first();

                if (!$existingAssignment) {
                    $assignment = ReadingTaskAssignment::create([
                        'reading_task_id' => $taskId,
                        'classroom_id' => $classroomId,
                        'assigned_by' => $user->id,
                        'due_date' => $request->due_date,
                        'assigned_at' => now(),
                    ]);

                    $assignments[] = $assignment->load(['classroom', 'assignedBy', 'readingTask']);
                }
            }
        }

        return response()->json([
            'message' => 'Bulk assignment completed',
            'assignments' => ReadingTaskAssignmentResource::collection(collect($assignments)),
            'errors' => $errors,
        ]);
    }
}