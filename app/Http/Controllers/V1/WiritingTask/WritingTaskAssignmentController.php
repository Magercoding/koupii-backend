<?php

namespace App\Http\Controllers\V1\WiritingTask;

use App\Http\Controllers\Controller;
use App\Models\WritingTask;
use App\Services\V1\WritingTask\WritingTaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class WritingTaskAssignmentController extends Controller implements HasMiddleware
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
        $task = WritingTask::findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'exists:classrooms,id'
        ]);

        try {
            $service = new WritingTaskAssignmentService();
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
     * Remove task from classroom.
     */
    public function removeFromClassroom(Request $request, string $id, string $classroomId)
    {
        $task = WritingTask::findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new WritingTaskAssignmentService();
            $service->removeFromClassroom($task, $classroomId);

            return response()->json([
                'message' => 'Task removed from classroom successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove task from classroom',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get task assignments.
     */
    public function getAssignments(string $id)
    {
        $task = WritingTask::with('assignments.classroom')->findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Task assignments retrieved successfully',
            'data' => $task->assignments,
        ], 200);
    }
}