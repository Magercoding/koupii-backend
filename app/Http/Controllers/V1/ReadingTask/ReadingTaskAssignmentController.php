<?php

namespace App\Http\Controllers\V1\ReadingTask;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ReadingTask;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ReadingTaskAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('role:admin,teacher', except: ['getAssignments']),
        ];
    }

    public function getAssignments(Request $request, string $id)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        if ($user->role === 'student') {
            return response()->json(['message' => 'Access denied'], 403);
        }
        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $assignments = Assignment::where('task_id', $id)
            ->where('task_type', 'reading_task')
            ->with(['class', 'assignedBy'])
            ->get();

        return response()->json(['data' => $assignments]);
    }

    public function assignToClassrooms(Request $request, string $id)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'classroom_ids'   => 'required|array',
            'classroom_ids.*' => 'exists:classes,id',
            'due_date'        => 'nullable|date|after:now',
        ]);

        $assignments = [];
        foreach ($request->classroom_ids as $classroomId) {
            $existing = Assignment::where('task_id', $id)
                ->where('task_type', 'reading_task')
                ->where('class_id', $classroomId)
                ->first();

            if (!$existing) {
                $assignments[] = Assignment::create([
                    'id'          => Str::uuid(),
                    'class_id'    => $classroomId,
                    'task_id'     => $id,
                    'task_type'   => 'reading_task',
                    'assigned_by' => $user->id,
                    'title'       => $task->title,
                    'due_date'    => $request->due_date,
                    'is_published'=> true,
                    'status'      => 'active',
                    'source_type' => 'manual',
                    'type'        => 'reading',
                    'max_attempts'=> 3,
                ]);
            }
        }

        return response()->json(['message' => 'Task assigned successfully', 'assignments' => $assignments]);
    }

    public function removeFromClassroom(Request $request, string $id, string $classroomId)
    {
        $user = $request->user();
        $task = ReadingTask::findOrFail($id);

        if ($user->role === 'teacher' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        Assignment::where('task_id', $id)
            ->where('task_type', 'reading_task')
            ->where('class_id', $classroomId)
            ->delete();

        return response()->json(['message' => 'Assignment removed successfully']);
    }

    public function bulkAssign(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'task_ids'        => 'required|array',
            'task_ids.*'      => 'exists:reading_tasks,id',
            'classroom_ids'   => 'required|array',
            'classroom_ids.*' => 'exists:classes,id',
            'due_date'        => 'nullable|date|after:now',
        ]);

        $assignments = [];
        $errors = [];

        foreach ($request->task_ids as $taskId) {
            $task = ReadingTask::find($taskId);
            if ($user->role === 'teacher' && $task->created_by !== $user->id) {
                $errors[] = "Access denied for task: {$task->title}";
                continue;
            }

            foreach ($request->classroom_ids as $classroomId) {
                $existing = Assignment::where('task_id', $taskId)
                    ->where('task_type', 'reading_task')
                    ->where('class_id', $classroomId)
                    ->first();

                if (!$existing) {
                    $assignments[] = Assignment::create([
                        'id'          => Str::uuid(),
                        'class_id'    => $classroomId,
                        'task_id'     => $taskId,
                        'task_type'   => 'reading_task',
                        'assigned_by' => $user->id,
                        'title'       => $task->title,
                        'due_date'    => $request->due_date,
                        'is_published'=> true,
                        'status'      => 'active',
                        'source_type' => 'manual',
                        'type'        => 'reading',
                        'max_attempts'=> 3,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Bulk assignment completed', 'assignments' => $assignments, 'errors' => $errors]);
    }
}
