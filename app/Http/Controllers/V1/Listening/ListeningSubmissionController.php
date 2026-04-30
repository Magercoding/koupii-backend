<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\SubmitListeningRequest;
use App\Http\Resources\V1\Listening\ListeningSubmissionResource;
use App\Models\Assignment;
use App\Models\ListeningTask;
use App\Models\ListeningSubmission;
use App\Services\V1\Listening\ListeningSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ListeningSubmissionController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ListeningSubmissionService $service
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get submissions (Teacher view — filter by task_id query param).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $taskId = $request->query('task_id');
        $assignmentId = $request->query('assignment_id');

        if ($taskId) {
            $task = ListeningTask::findOrFail($taskId);

            // Check authorization — listening_tasks uses created_by
            if ($user->role !== 'admin' && $task->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $submissions = ListeningSubmission::query()->where('listening_task_id', '=', $taskId)->with(['student', 'task', 'review'])
                ->orderBy('submitted_at', 'desc')
                ->get();
        } else {
            // If no task_id, return student's own submissions or teacher's task submissions
            if ($user->role === 'student') {
                if ($assignmentId) {
                    // Resolve the task_id from the assignment so we can fetch ALL attempts
                    // for that task (attempt_number is global per task/student, not per assignment)
                    $assignment = \App\Models\Assignment::find($assignmentId);
                    $resolvedTaskId = $assignment?->task_id;

                    if ($resolvedTaskId) {
                        // Return all submitted attempts for this task by this student
                        $submissions = ListeningSubmission::query()
                            ->where('listening_task_id', $resolvedTaskId)
                            ->where('student_id', $user->id)
                            ->with(['task', 'review'])
                            ->orderBy('attempt_number', 'asc')
                            ->get();
                    } else {
                        // Fallback: filter directly by assignment_id
                        $submissions = ListeningSubmission::query()
                            ->where('student_id', $user->id)
                            ->where('assignment_id', $assignmentId)
                            ->with(['task', 'review'])
                            ->orderBy('attempt_number', 'asc')
                            ->get();
                    }
                } else {
                    $submissions = ListeningSubmission::query()->where('student_id', '=', $user->id)
                        ->with(['task', 'review'])
                        ->orderBy('submitted_at', 'desc')
                        ->get();
                }
            } elseif ($user->role === 'admin') {
                $submissions = ListeningSubmission::with(['student', 'task', 'review'])
                    ->orderBy('submitted_at', 'desc')
                    ->limit(50)
                    ->get();
            } else {
                // Teacher: get all submissions for tasks they created
                $submissions = ListeningSubmission::query()->whereHas('task', function ($q) use ($user) {
                        $q->where('created_by', '=', $user->id);
                    })->with(['student', 'task', 'review'])
                    ->orderBy('submitted_at', 'desc')
                    ->get();
            }
        }

        return response()->json([
            'message' => 'Submissions retrieved successfully',
            'data' => ListeningSubmissionResource::collection($submissions),
        ], 200);
    }

    /**
     * Submit a listening task (Student submits work).
     * Route: POST /api/v1/listening/submissions
     * task_id is sent in the request body, not as a URL parameter.
     */
    public function store(SubmitListeningRequest $request)
    {
        $user = Auth::user();
        $taskId = $request->input('task_id') ?? $request->input('listening_task_id');
        $assignmentId = $request->input('assignment_id');

        if (!$taskId) {
            return response()->json(['message' => 'task_id is required'], 422);
        }

        // Resolve task if the ID provided is an assignment_id
        $task = ListeningTask::find($taskId);
        if (!$task) {
            $assignment = \App\Models\Assignment::find($taskId);
            if ($assignment && $assignment->task_type === 'listening_task') {
                $task = ListeningTask::find($assignment->task_id);
            }
        }

        if (!$task) {
            $task = ListeningTask::findOrFail($taskId);
        }

        // Check if user has access to this task
        $hasAccess = false;
        
        if ($user->role === 'student') {
            $hasAccess = $this->isStudentAssigned($task, $user, $assignmentId);

            // Fallback: if task is published, allow access (for practice/public tasks)
            if (!$hasAccess && $task->is_published) {
                $hasAccess = true;
            }
        } elseif ($user->role === 'teacher') {
            $hasAccess = $task->created_by === $user->id || $task->is_published;
        } elseif ($user->role === 'admin') {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            return response()->json(['message' => 'Unauthorized access to this task'], 403);
        }

        try {
            $submission = $this->service->startSubmission($task, $user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Listening submission started successfully',
                'data' => new ListeningSubmissionResource($submission),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('ListeningSubmissionController@store: ' . $e->getMessage(), [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start submission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific submission details.
     */
    public function show(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::with(['task.questions', 'student', 'review', 'answers'])
            ->findOrFail($submissionId);

        $user = Auth::user();

        // Check authorization — listening_tasks uses created_by
        if ($user->role === 'student' && $submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'teacher' && $submission->task->created_by !== $user->id) {
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

        $submissions = ListeningSubmission::with(['task', 'review', 'answers'])
            ->where('listening_task_id', $taskId)
            ->where('student_id', $student->id)
            ->where(function($q) {
                $q->where('assignment_id', request()->query('assignment_id'))
                  ->orWhereNull('assignment_id');
            })
            ->orderBy('attempt_number', 'desc')
            ->get();

        return response()->json([
            'message' => 'Your submissions retrieved successfully',
            'data' => ListeningSubmissionResource::collection($submissions),
        ], 200);
    }

    /**
     * Auto-save student answers.
     */
    public function autoSave(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::findOrFail($submissionId);
        $user = Auth::user();

        if ($submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $submission = $this->service->saveDraft($submission->task, $user, $request->all());

            return response()->json([
                'message' => 'Draft saved successfully',
                'data' => new ListeningSubmissionResource($submission),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save draft',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize and submit listening task.
     */
    public function submit(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::with('task')->findOrFail($submissionId);
        $user = Auth::user();

        if ($submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $submission = $this->service->submitById($submission, $user, $request->all());

            return response()->json([
                'message' => 'Listening submission finalized successfully',
                'data' => new ListeningSubmissionResource($submission),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to finalize submission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update submission status (for resubmission).
     */
    public function updateStatus(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::with('task')->findOrFail($submissionId);
        $user = Auth::user();

        // Only teachers (task owner) or admin can update submission status
        if ($user->role !== 'admin' && $submission->task->created_by !== $user->id) {
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

    private function isStudentAssigned(ListeningTask $task, $user, $assignmentId = null): bool
    {
        // 1. If explicit assignment_id is provided, check it directly
        if ($assignmentId) {
            $assignment = Assignment::find($assignmentId);
            if ($assignment && $assignment->task_id == $task->id && $assignment->task_type === 'listening_task') {
                return (bool) \Illuminate\Support\Facades\DB::table('student_assignments')
                    ->where('assignment_id', $assignmentId)
                    ->where('student_id', $user->id)
                    ->exists();
            }
        }

        // 2. Check student_assignments table indirectly
        $hasStudentAssignment = \Illuminate\Support\Facades\DB::table('student_assignments')
            ->join('assignments', 'student_assignments.assignment_id', '=', 'assignments.id')
            ->where('student_assignments.student_id', $user->id)
            ->where('assignments.task_id', $task->id)
            ->where('assignments.task_type', 'listening_task')
            ->exists();

        if ($hasStudentAssignment) {
            return true;
        }

        // 3. Check global assignments table (Class-based link)
        $hasGlobalAssignment = Assignment::where('task_id', $task->id)
            ->where('task_type', 'listening_task')
            ->whereHas('class.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();

        if ($hasGlobalAssignment) {
            return true;
        }

        // 4. Check legacy task-specific assignments
        return $task->assignments()
            ->whereHas('classroom.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();
    }
}
