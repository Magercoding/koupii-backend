<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\CreateQuestionRequest;
use App\Http\Requests\V1\Listening\UpdateQuestionRequest;
use App\Http\Resources\V1\Listening\ListeningQuestionResource;
use App\Models\ListeningTask;
use App\Models\TestQuestion;
use App\Services\V1\Listening\ListeningQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningQuestionController extends Controller
{
    public function __construct(
        private ListeningQuestionService $listeningQuestionService
    ) {}

    /**
     * Get all questions for a listening task
     */
    public function index(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $questions = $this->listeningQuestionService->getTaskQuestions($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => ListeningQuestionResource::collection($questions),
                'message' => 'Listening questions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve questions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new question for listening task
     */
    public function store(CreateQuestionRequest $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $questionData = $request->validated();
            $question = $this->listeningQuestionService->createQuestion($listeningTask, $questionData);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningQuestionResource($question),
                'message' => 'Question created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a specific question
     */
    public function show(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            $questionDetails = $this->listeningQuestionService->getQuestionDetails($question);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningQuestionResource($questionDetails),
                'message' => 'Question retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a question
     */
    public function update(UpdateQuestionRequest $request, ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            $questionData = $request->validated();
            $updatedQuestion = $this->listeningQuestionService->updateQuestion($question, $questionData);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningQuestionResource($updatedQuestion),
                'message' => 'Question updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a question
     */
    public function destroy(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            $this->listeningQuestionService->deleteQuestion($question);

            return response()->json([
                'status' => 'success',
                'message' => 'Question deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete question: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get available question types for listening tasks
     */
    public function getQuestionTypes(): JsonResponse
    {
        try {
            $questionTypes = $this->listeningQuestionService->getAvailableQuestionTypes();

            return response()->json([
                'status' => 'success',
                'data' => $questionTypes,
                'message' => 'Question types retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve question types: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk create questions from template
     */
    public function bulkCreate(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate([
                'questions' => 'required|array|min:1',
                'questions.*.question_type' => 'required|string',
                'questions.*.question_text' => 'required|string',
                'questions.*.points' => 'nullable|numeric|min:0',
                'questions.*.options' => 'nullable|array',
                'questions.*.question_data' => 'nullable|array'
            ]);

            $questions = $this->listeningQuestionService->bulkCreateQuestions(
                $listeningTask,
                $request->questions
            );

            return response()->json([
                'status' => 'success',
                'data' => ListeningQuestionResource::collection($questions),
                'message' => 'Questions created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create questions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reorder questions
     */
    public function reorder(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate([
                'question_orders' => 'required|array',
                'question_orders.*.question_id' => 'required|string|exists:test_questions,id',
                'question_orders.*.order' => 'required|integer|min:1'
            ]);

            $this->listeningQuestionService->reorderQuestions($listeningTask, $request->question_orders);

            return response()->json([
                'status' => 'success',
                'message' => 'Questions reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder questions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Preview question for students
     */
    public function preview(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            $preview = $this->listeningQuestionService->getQuestionPreview($question);

            return response()->json([
                'status' => 'success',
                'data' => $preview,
                'message' => 'Question preview generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate preview: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}