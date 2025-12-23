<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\StoreListeningTaskRequest;
use App\Http\Requests\V1\Listening\UpdateListeningTaskRequest;
use App\Http\Resources\V1\Listening\ListeningTaskResource;
use App\Models\ListeningTask;
use App\Services\V1\Listening\ListeningTaskService;
use App\Services\V1\Listening\ListeningTaskDeleteService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ListeningTaskController extends Controller implements HasMiddleware 
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Display a listing of listening tasks.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ListeningTask::with(['creator', 'assignments.classroom']);

        // Role-based access control
        if ($user->role === 'admin') {
            // Admin sees all tasks
        } elseif ($user->role === 'student') {
            // Students see only published tasks assigned to their classrooms
            $query->where('is_published', true)
                ->whereHas('assignments.classroom.students', function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                })
                ->with([
                    'submissions' => function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    }
                ]);
        } else {
            // Teachers see only their own tasks
            $query->where('creator_id', $user->id)
                ->with(['submissions.review', 'assignments']);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Listening tasks retrieved successfully',
            'data' => ListeningTaskResource::collection($tasks),
        ], 200);
    }

    /**
     * Store a newly created listening task.
     */
    public function store(StoreListeningTaskRequest $request, ListeningTaskService $service)
    {
        try {
            $task = $service->create($request->validated(), $request);

            return response()->json([
                'message' => 'Listening task created successfully',
                'data' => new ListeningTaskResource($task),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create listening task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified listening task.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $query = ListeningTask::with([
            'creator',
            'assignments.classroom',
            'submissions.student',
            'submissions.review'
        ])->where('id', $id);

        // Role-based access control
        if ($user->role === 'student') {
            $query->where('is_published', true)
                ->whereHas('assignments.classroom.students', function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                });
        } elseif ($user->role !== 'admin') {
            $query->where('creator_id', $user->id);
        }

        $task = $query->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found or unauthorized access',
            ], 404);
        }

        return response()->json([
            'message' => 'Listening task retrieved successfully',
            'data' => new ListeningTaskResource($task),
        ], 200);
    }

    /**
     * Update the specified listening task.
     */
    public function update(UpdateListeningTaskRequest $request, string $id)
    {
        $task = ListeningTask::findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            $updatedTask = (new ListeningTaskService())->updateTask($task, $request->validated(), $request);

            DB::commit();

            return response()->json([
                'message' => 'Listening task updated successfully',
                'data' => new ListeningTaskResource($updatedTask),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified listening task.
     */
    public function destroy(string $id)
    {
        $task = ListeningTask::findOrFail($id);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new ListeningTaskDeleteService();
            $response = $service->deleteTask($id);

            return response()->json(
                ['message' => $response['message'] ?? $response['error']],
                $response['status']
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete task',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}