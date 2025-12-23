<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\SubmitListeningRequest;
use App\Http\Resources\V1\Listening\ListeningSubmissionResource;
use App\Models\ListeningTask;
use App\Models\ListeningSubmission;
use App\Services\V1\Listening\ListeningSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ListeningSubmissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get submissions for a task (Teacher view).
     */
    public function index(Request $request, string $taskId)
    {
        $task = ListeningTask::findOrFail($taskId);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submissions = ListeningSubmission::with(['student', 'task', 'review'])
            ->where('task_id', $taskId)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Submissions retrieved successfully',
            'data' => ListeningSubmissionResource::collection($submissions),
        ], 200);
    }

    /**
     * Submit a listening task (Student submits work).
     */
    public function store(SubmitListeningRequest $request, string $taskId)
    {
        $task = ListeningTask::findOrFail($taskId);
        $student = Auth::user();

        // Check if student has access to this task
        $hasAccess = $task->assignments()
            ->whereHas('classroom.students', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })->exists();

        if (!$hasAccess) {
            return response()->json(['message' => 'Unauthorized access to this task'], 403);
        }

        try {
            $service = new ListeningSubmissionService();
            $submission = $service->submit($task, $student, $request->validated(), $request);

            return response()->json([
                'message' => 'Listening submission created successfully',
                'data' => new ListeningSubmissionResource($submission),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create submission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific submission details.
     */
    public function show(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::with(['task', 'student', 'review', 'answers'])
            ->findOrFail($submissionId);

        $user = Auth::user();

        // Check authorization
        if ($user->role === 'student' && $submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'teacher' && $submission->task->creator_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Submission retrieved successfully',
            'data' => new ListeningSubmissionResource($submission),
        ], 200);
    }

    /**
     * Get student's own submissions for a task.
     */
    public function getMySubmissions(Request $request, string $taskId)
    {
        $student = Auth::user();

        $submissions = ListeningSubmission::with(['task', 'review'])
            ->where('task_id', $taskId)
            ->where('student_id', $student->id)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Your submissions retrieved successfully',
            'data' => ListeningSubmissionResource::collection($submissions),
        ], 200);
    }

    /**
     * Update submission status (for resubmission).
     */
    public function updateStatus(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::findOrFail($submissionId);
        $user = Auth::user();

        // Only teachers can update submission status
        if ($user->role !== 'admin' && $submission->task->creator_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:to_do,submitted,reviewed,done'
        ]);

        $submission->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Submission status updated successfully',
            'data' => new ListeningSubmissionResource($submission),
        ], 200);
    }
}
