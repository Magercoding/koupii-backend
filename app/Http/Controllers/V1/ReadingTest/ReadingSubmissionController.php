<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReadingTest\StartReadingTestRequest;
use App\Http\Resources\V1\ReadingTest\ReadingSubmissionResource;
use App\Http\Resources\V1\ReadingTest\ReadingTestResource;
use App\Services\V1\ReadingTest\ReadingSubmissionService;
use App\Models\Test;
use App\Models\ReadingTask;
use App\Models\ReadingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;

class ReadingSubmissionController extends Controller implements HasMiddleware
{
    public function __construct(
        private ReadingSubmissionService $submissionService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get student's reading test assignments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $submissions = $this->submissionService->getStudentSubmissions(
                auth()->id(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reading assignments retrieved successfully',
                'data' => ReadingSubmissionResource::collection($submissions)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a new reading test attempt (Legacy Test)
     */
    public function start(StartReadingTestRequest $request, Test $test): JsonResponse
    {
        try {
            $submission = $this->submissionService->startTest(
                $test,
                auth()->id(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reading test started successfully',
                'data' => new ReadingSubmissionResource($submission)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start test',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Start a new reading task attempt (New ReadingTask)
     */
    public function submit(Request $request, string $taskId): JsonResponse
    {
        try {
            // Resolve task if the ID provided is an assignment_id
            $task = ReadingTask::find($taskId);
            if (!$task) {
                $assignment = \App\Models\Assignment::find($taskId);
                if ($assignment && $assignment->task_type === 'reading_task') {
                    $task = ReadingTask::find($assignment->task_id);
                }
            }

            if (!$task) {
                $task = ReadingTask::findOrFail($taskId);
            }

            // Sync assignment_id if payload is from assignment list
            if (!$request->has('assignment_id') && $taskId !== $task->id) {
                $request->merge(['assignment_id' => $taskId]);
            }
            $submission = $this->submissionService->startReadingTask(
                $task,
                auth()->id(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reading task started successfully',
                'data' => new ReadingSubmissionResource($submission)
            ], 201);
        } catch (\Exception $e) {
            Log::error('ReadingSubmissionController@submit: Error', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start task: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get task/test details for student
     */
    public function show(string $taskId): JsonResponse
    {
        try {
            $task = ReadingTask::with(['submissions' => function($q) {
                $q->where('student_id', auth()->id());
            }])->find($taskId);
            if ($task) {
                return response()->json([
                    'success' => true,
                    'data' => new \App\Http\Resources\V1\ReadingTask\ReadingTaskResource($task)
                ]);
            }

            $test = Test::findOrFail($taskId);
            $testDetails = $this->submissionService->getTestForStudent($test, auth()->id());

            return response()->json([
                'success' => true,
                'data' => new ReadingTestResource($testDetails)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve details',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mark submission as complete
     */
    public function complete(Request $request, string $submissionId): JsonResponse
    {
        try {
            $submission = ReadingSubmission::findOrFail($submissionId);
            
            // Check if user owns this submission
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Handle optional time taken from request
            if ($request->has('time_taken_seconds')) {
                $submission->time_taken_seconds = (int) $request->input('time_taken_seconds');
                $submission->save();
            }

            $submission = $this->submissionService->completeSubmission($submission);

            return response()->json([
                'success' => true,
                'message' => 'Submission completed successfully',
                'data' => new ReadingSubmissionResource($submission)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get submission details
     */
    public function getSubmission(ReadingSubmission $submission): JsonResponse
    {
        try {
            // Check if user owns this submission
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $submissionDetails = $this->submissionService->getSubmissionWithDetails($submission);

            return response()->json([
                'success' => true,
                'data' => new ReadingSubmissionResource($submissionDetails)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}