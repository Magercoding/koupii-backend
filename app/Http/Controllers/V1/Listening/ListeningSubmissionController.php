<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\StartListeningTestRequest;
use App\Http\Requests\V1\Listening\SubmitListeningTestRequest;
use App\Http\Resources\V1\Listening\ListeningSubmissionResource;
use App\Http\Resources\V1\Listening\ListeningTestDetailsResource;
use App\Http\Resources\V1\Listening\ListeningResultResource;
use App\Models\Test;
use App\Models\ListeningSubmission;
use App\Services\V1\Listening\ListeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningSubmissionController extends Controller
{
    public function __construct(
        private ListeningService $listeningService
    ) {}

    public function start(StartListeningTestRequest $request, Test $test): JsonResponse
    {
        try {
            $student = $request->user();
            $submission = $this->listeningService->startTest($test, $student);

            return response()->json([
                'status' => 'success',
                'message' => 'Listening test started successfully',
                'data' => new ListeningSubmissionResource($submission)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function show(Test $test): JsonResponse
    {
        $test->load(['passages.audioSegments', 'questions.options']);
        
        return response()->json([
            'status' => 'success',
            'data' => new ListeningTestDetailsResource($test)
        ]);
    }

    public function submit(SubmitListeningTestRequest $request, ListeningSubmission $submission): JsonResponse
    {
        try {
            $result = $this->listeningService->submitTest(
                $submission,
                $request->validated()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Listening test submitted successfully',
                'data' => new ListeningResultResource($result)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getSubmission(ListeningSubmission $submission): JsonResponse
    {
        $submission->load(['answers.question', 'test']);
        
        return response()->json([
            'status' => 'success',
            'data' => new ListeningSubmissionResource($submission)
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = ListeningSubmission::where('student_id', $request->user()->id)
            ->with(['test', 'answers']);

        if ($request->test_id) {
            $query->where('test_id', $request->test_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => ListeningSubmissionResource::collection($submissions)
        ]);
    }
}