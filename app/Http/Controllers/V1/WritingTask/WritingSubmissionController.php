<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WritingTask\SubmitWritingRequest;
use App\Http\Resources\V1\WritingTask\WritingSubmissionResource;
use App\Models\WritingTask;
use App\Models\WritingSubmission;
use App\Services\V1\WritingTask\WritingSubmissionService;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WritingSubmissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }
    /**
     * Submit student writing.
     */
    public function submit(SubmitWritingRequest $request, string $taskId)
    {
        // Resolve task if the ID provided is an assignment_id
        $task = WritingTask::find($taskId);
        if (!$task) {
            $assignment = \App\Models\Assignment::find($taskId);
            if ($assignment && $assignment->task_type === 'writing_task') {
                $task = WritingTask::find($assignment->task_id);
            }
        }

        if (!$task) {
            $task = WritingTask::findOrFail($taskId); // Fallback to 404 with error if not found
        }

        $assignmentId = $request->input('assignment_id') ?? ($task->id !== $taskId ? $taskId : null);

        // Check if student can access this task
        if (!$this->studentCanAccessTask($task, Auth::user(), $assignmentId)) {
            return response()->json(['message' => 'Task not found or unauthorized'], 404);
        }

        try {
            $service = new WritingSubmissionService();
            // Pass assignment_id to the service
            $submission = $service->submitWriting($task, array_merge($request->validated(), [
                'assignment_id' => $assignmentId
            ]));

            return response()->json([
                'message' => 'Writing submitted successfully',
                'data' => new WritingSubmissionResource($submission),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit writing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save draft (auto-save functionality).
     */
    public function saveDraft(Request $request, string $taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        $assignmentId = $request->input('assignment_id');

        // Check if student can access this task
        if (!$this->studentCanAccessTask($task, Auth::user(), $assignmentId)) {
            return response()->json(['message' => 'Task not found or unauthorized'], 404);
        }

        $request->validate([
            'content' => 'required|string',
            'files' => 'nullable|array',
            'time_taken_seconds' => 'nullable|integer|min:0'
        ]);

        try {
            $service = new WritingSubmissionService();
            $submission = $service->saveDraft($task, $request->all());

            return response()->json([
                'message' => 'Draft saved successfully',
                'data' => new WritingSubmissionResource($submission),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save draft',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create retake submission.
     */
    public function createRetake(Request $request, string $taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        $assignmentId = $request->input('assignment_id');

        // Check if student can access this task
        if (!$this->studentCanAccessTask($task, Auth::user(), $assignmentId)) {
            return response()->json(['message' => 'Task not found or unauthorized'], 404);
        }

        $request->validate([
            'retake_option' => ['required', Rule::in(['rewrite_all', 'group_similar', 'choose_any'])],
            'chosen_mistakes' => 'required_if:retake_option,choose_any|array'
        ]);

        try {
            $service = new WritingSubmissionService();
            $submission = $service->createRetakeSubmission($task, $request->retake_option, $request->all());

            return response()->json([
                'message' => 'Retake created successfully',
                'data' => new WritingSubmissionResource($submission),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create retake',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark submission as done (Student acknowledges review).
     */
    public function markAsDone(Request $request, string $taskId, string $submissionId)
    {
        $submission = WritingSubmission::findOrFail($submissionId);

        // Check if student owns this submission
        if ($submission->student_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($submission->status !== 'reviewed') {
            return response()->json(['message' => 'Submission must be reviewed first'], 400);
        }

        try {
            $service = new WritingSubmissionService();
            $updatedSubmission = $service->markAsDone($submission);

            return response()->json([
                'message' => 'Submission marked as done successfully',
                'data' => new WritingSubmissionResource($updatedSubmission),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark as done',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific submission detail.
     */
    public function show(Request $request, string $taskId, string $submissionId)
    {
        $submission = WritingSubmission::with(['student', 'review', 'writingTask'])
            ->findOrFail($submissionId);

        $user = Auth::user();

        // Check authorization
        if ($user->role === 'student' && $submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'teacher' && $submission->writingTask->creator_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Submission retrieved successfully',
            'data' => new WritingSubmissionResource($submission),
        ], 200);
    }

    /**
     * Get submissions for a task (Teacher view).
     */
    public function index(Request $request, string $taskId)
    {
        $task = WritingTask::findOrFail($taskId);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submissions = WritingSubmission::where('writing_task_id', $taskId)
            ->with(['student', 'review'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Submissions retrieved successfully',
            'data' => WritingSubmissionResource::collection($submissions),
        ], 200);
    }

    /**
     * Check if student can access a task.
     */
    private function studentCanAccessTask(WritingTask $task, $user, ?string $assignmentId = null): bool
    {
        if ($user->role !== 'student') {
            return true;
        }

        // 1. Check if specific assignment_id is provided and valid
        if ($assignmentId) {
            $hasAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                ->where('student_id', $user->id)
                ->exists();
            if ($hasAssignment) return true;
        }

        // 2. Check if the task is linked to any of student's assignments (indirect check)
        $hasAnyAssignment = \App\Models\StudentAssignment::where('student_id', $user->id)
            ->whereHas('assignment', function ($q) use ($task) {
                $q->where('task_id', $task->id)
                  ->where('task_type', 'writing_task');
            })->exists();
            
        if ($hasAnyAssignment) return true;

        // 3. Fallback: Check classroom-based assignments (Legacy)
        $hasLegacyAssignment = $task->assignments()
            ->whereHas('classroom.students', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->exists();

        return $hasLegacyAssignment || $task->is_published;
    }
}