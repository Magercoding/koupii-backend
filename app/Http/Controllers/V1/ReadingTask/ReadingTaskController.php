<?php

namespace App\Http\Controllers\V1\ReadingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReadingTask\StoreReadingTaskRequest;
use App\Http\Requests\V1\ReadingTask\UpdateReadingTaskRequest;
use App\Http\Resources\V1\ReadingTask\ReadingTaskResource;
use App\Models\ReadingTask;
use App\Services\V1\ReadingTask\ReadingTaskService;
use App\Services\V1\ReadingTask\ReadingTaskDeleteService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReadingTaskController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Display a listing of reading tasks.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ReadingTask::with(['creator', 'assignments']);

        // Role-based access control
        if ($user->role === 'admin') {
            // Admin sees all tasks
        } elseif ($user->role === 'student') {
            // Students see only published tasks assigned to their classes
            $query->where('is_published', true)
                ->whereHas('assignments', function ($q) use ($user) {
                    $q->whereHas('class.enrollments', function ($e) use ($user) {
                        $e->where('student_id', $user->id)->where('status', 'active');
                    });
                })
                ->with([
                    'submissions' => function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    }
                ]);
        } else {
            // Teachers see their own tasks and published tasks
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('is_published', true);
            });
        }

        // Apply filters
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return ReadingTaskResource::collection($tasks);
    }

    /**
     * Store a newly created reading task.
     */
    public function store(StoreReadingTaskRequest $request)
    {
        try {
            $validated = $request->validated();
            \Illuminate\Support\Facades\Log::info('ReadingTaskController: Validated data', ['data' => $validated]);

            $service = app(ReadingTaskService::class);
            $task = $service->create($validated);

            return new ReadingTaskResource($task->load(['creator', 'assignments']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create reading task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified reading task.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        
        \Illuminate\Support\Facades\Log::info('ReadingTaskController@show: Fetching task', [
            'task_id' => $id,
            'user_id' => $user->id ?? 'anonymous',
            'role' => $user->role ?? 'none'
        ]);

        $task = ReadingTask::with([
            'creator',
            'submissions' => function ($query) use ($user) {
                if ($user && $user->role === 'student') {
                    $query->where('student_id', $user->id);
                }
            }
        ])->findOrFail($id);

        // Check permissions
        if ($user->role === 'student') {
            $isAssigned = $this->isStudentAssigned($task, $user);
            
            if (!$isAssigned) {
                return response()->json(['message' => 'Access denied'], 403);
            }
        }

        if (
            $user->role === 'teacher'
            && $task->created_by !== $user->id
            && !$task->is_published
            && !$this->isTeacherAssigned($task, $user)
        ) {
            \Illuminate\Support\Facades\Log::warning('ReadingTask: Access Denied for teacher', [
                'user_id' => $user->id,
                'task_id' => $task->id
            ]);
            return response()->json(['message' => 'Access denied'], 403);
        }

        return new ReadingTaskResource($task);
    }

    /**
     * Update the specified reading task.
     */
    public function update(UpdateReadingTaskRequest $request, string $id)
    {
        $user = $request->user();

        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role !== 'admin' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        try {
            $service = app(ReadingTaskService::class);
            $task = $service->update($task, $request->validated());

            return new ReadingTaskResource($task->load(['creator', 'assignments']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update reading task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified reading task.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role !== 'admin' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        try {
            $service = app(ReadingTaskDeleteService::class);
            $service->delete($task);

            return response()->json(['message' => 'Reading task deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete reading task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the published status of a reading task.
     */
    public function togglePublish(Request $request, string $id)
    {
        $user = $request->user();

        $task = ReadingTask::findOrFail($id);

        // Check permissions
        if ($user->role !== 'admin' && $task->created_by !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $task->update(['is_published' => !$task->is_published]);

        return new ReadingTaskResource($task->load(['creator', 'assignments']));
    }

    /**
     * Check if student is assigned to the task.
     */
    private function isStudentAssigned(ReadingTask $task, $user): bool
    {
        // 1. Check newer generic StudentAssignment system (Direct student link)
        $hasStudentAssignment = \Illuminate\Support\Facades\DB::table('student_assignments')
            ->join('assignments', 'student_assignments.assignment_id', '=', 'assignments.id')
            ->where('student_assignments.student_id', $user->id)
            ->where('assignments.task_id', $task->id)
            ->where('assignments.task_type', 'reading_task')
            ->exists();

        if ($hasStudentAssignment) {
            return true;
        }

        // 2. Check newer generic Assignment system (Class-based link)
        $hasGlobalAssignment = \Illuminate\Support\Facades\DB::table('assignments')
            ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
            ->where('assignments.task_id', $task->id)
            ->where('assignments.task_type', 'reading_task')
            ->where('class_enrollments.student_id', $user->id)
            ->exists();

        if ($hasGlobalAssignment) {
            return true;
        }

        // 3. Check class-based assignment (student enrolled in class that has this task assigned)
        return \Illuminate\Support\Facades\DB::table('assignments')
            ->join('class_enrollments', 'assignments.class_id', '=', 'class_enrollments.class_id')
            ->where('assignments.task_id', $task->id)
            ->where('assignments.task_type', 'reading_task')
            ->where('class_enrollments.student_id', $user->id)
            ->where('class_enrollments.status', 'active')
            ->exists();
    }

    /**
     * Check if teacher owns a class that has this task assigned.
     */
    private function isTeacherAssigned(ReadingTask $task, $user): bool
    {
        return \Illuminate\Support\Facades\DB::table('assignments')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->where('assignments.task_id', $task->id)
            ->where('assignments.task_type', 'reading_task')
            ->where('classes.teacher_id', $user->id)
            ->exists();
    }
}

