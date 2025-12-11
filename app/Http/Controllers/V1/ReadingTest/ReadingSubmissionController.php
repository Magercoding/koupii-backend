<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReadingTest\StartReadingTestRequest;
use App\Http\Resources\V1\ReadingTest\ReadingSubmissionResource;
use App\Http\Resources\V1\ReadingTest\ReadingTestResource;
use App\Services\V1\ReadingTest\ReadingSubmissionService;
use App\Models\Test;
use App\Models\ReadingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
     * Start a new reading test attempt
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
     * Get test details for student
     */
    public function show(Test $test): JsonResponse
    {
        try {
            $testDetails = $this->submissionService->getTestForStudent($test, auth()->id());

            return response()->json([
                'success' => true,
                'data' => new ReadingTestResource($testDetails)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve test',
                'error' => $e->getMessage()
            ], 404);
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