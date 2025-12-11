<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReadingTest\SubmitAnswerRequest;
use App\Http\Requests\V1\ReadingTest\SubmitTestRequest;
use App\Http\Resources\V1\ReadingTest\ReadingSubmissionResource;
use App\Services\V1\ReadingTest\ReadingAnswerService;
use App\Models\ReadingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReadingAnswerController extends Controller implements HasMiddleware
{
    public function __construct(
        private ReadingAnswerService $answerService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Submit answer for a specific question
     */
    public function submitAnswer(SubmitAnswerRequest $request, ReadingSubmission $submission): JsonResponse
    {
        try {
            // Check ownership
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $answer = $this->answerService->submitAnswer(
                $submission,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Answer submitted successfully',
                'data' => $answer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answer',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Submit the entire test
     */
    public function submitTest(SubmitTestRequest $request, ReadingSubmission $submission): JsonResponse
    {
        try {
            // Check ownership
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $completedSubmission = $this->answerService->submitTest(
                $submission,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully',
                'data' => new ReadingSubmissionResource($completedSubmission)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit test',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get test results with explanations
     */
    public function getResults(ReadingSubmission $submission): JsonResponse
    {
        try {
            // Check ownership
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($submission->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not completed yet'
                ], 400);
            }

            $results = $this->answerService->getResultsWithExplanations($submission);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get results',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}