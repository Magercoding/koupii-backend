<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Models\ListeningTask;
use App\Services\V1\Listening\ListeningTaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ListeningTaskAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Assign task to classrooms (Teacher sends out test).
     */
    public function assignToClassrooms(Request $request, string $id)
    {
        $task = ListeningTask::findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id'
        ]);

        try {
            $service = new ListeningTaskAssignmentService();
            $assignments = $service->assignToClassrooms($task, $request->classroom_ids);

            return response()->json([
                'message' => 'Task sent to classrooms successfully',
                'assignments_count' => count($assignments),
                'data' => $assignments,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove assignment from classroom.
     */
    public function removeFromClassroom(Request $request, string $taskId, string $classroomId)
    {
        $task = ListeningTask::findOrFail($taskId);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new ListeningTaskAssignmentService();
            $result = $service->removeFromClassroom($task, $classroomId);

            if ($result) {
                return response()->json([
                    'message' => 'Task removed from classroom successfully',
                ], 200);
            }

            return response()->json([
                'message' => 'Assignment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all assignments for a task.
     */
    public function getTaskAssignments(string $taskId)
    {
        $task = ListeningTask::with(['assignments.classroom'])->findOrFail($taskId);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Task assignments retrieved successfully',
            'data' => $task->assignments,
        ], 200);
    }

    /**
     * Get classroom's assigned tasks.
     */
    public function getClassroomTasks(string $classroomId)
    {
        $user = Auth::user();
        
        // Check if user has access to this classroom
        if ($user->role === 'student') {
            // Students can only access their own classroom's tasks
            $hasAccess = $user->studentClassrooms()->where('classroom_id', $classroomId)->exists();
        } elseif ($user->role === 'teacher') {
            // Teachers can access their own classroom's tasks
            $hasAccess = $user->teacherClassrooms()->where('classroom_id', $classroomId)->exists();
        } else {
            // Admin has access to all
            $hasAccess = true;
        }

        if (!$hasAccess) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new ListeningTaskAssignmentService();
            $tasks = $service->getClassroomTasks($classroomId);

            return response()->json([
                'message' => 'Classroom tasks retrieved successfully',
                'data' => $tasks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve classroom tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}