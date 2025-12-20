<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\SaveListeningAnswerRequest;
use App\Http\Resources\V1\Listening\ListeningQuestionAnswerResource;
use App\Models\ListeningQuestionAnswer;
use App\Models\ListeningSubmission;
use App\Services\V1\Listening\ListeningAnswerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ListeningAnswerController extends Controller
{
    public function __construct(
        private ListeningAnswerService $answerService
    ) {}

    public function store(SaveListeningAnswerRequest $request, ListeningSubmission $submission): JsonResponse
    {
        try {
            $answer = $this->answerService->saveAnswer($submission, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Answer saved successfully',
                'data' => new ListeningQuestionAnswerResource($answer)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(SaveListeningAnswerRequest $request, ListeningQuestionAnswer $answer): JsonResponse
    {
        try {
            $answer = $this->answerService->updateAnswer($answer, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Answer updated successfully',
                'data' => new ListeningQuestionAnswerResource($answer)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function index(ListeningSubmission $submission): JsonResponse
    {
        $answers = $submission->answers()->with(['question', 'selectedOption'])->get();

        return response()->json([
            'status' => 'success',
            'data' => ListeningQuestionAnswerResource::collection($answers)
        ]);
    }

    public function show(ListeningQuestionAnswer $answer): JsonResponse
    {
        $answer->load(['question', 'selectedOption']);

        return response()->json([
            'status' => 'success',
            'data' => new ListeningQuestionAnswerResource($answer)
        ]);
    }
}