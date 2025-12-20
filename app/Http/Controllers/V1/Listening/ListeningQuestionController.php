<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\CreateQuestionRequest;
use App\Http\Requests\V1\Listening\UpdateQuestionRequest;
use App\Http\Resources\V1\Listening\ListeningQuestionResource;
use App\Helpers\Listening\ListeningQuestionControllerHelper;
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
            return ListeningQuestionControllerHelper::successResponse($questions, 'Listening questions retrieved successfully');
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to retrieve questions', $e);
        }
    }

    /**
     * Create a new question for listening task
     */
    public function store(CreateQuestionRequest $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $question = $this->listeningQuestionService->createQuestion($listeningTask, $request->validated());
            return ListeningQuestionControllerHelper::successResponse($question, 'Question created successfully', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to create question', $e);
        }
    }

    /**
     * Display a specific question
     */
    public function show(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            if (!ListeningQuestionControllerHelper::validateQuestionOwnership($question, $listeningTask)) {
                return response()->json(['status' => 'error', 'message' => 'Question not found'], Response::HTTP_NOT_FOUND);
            }

            $questionDetails = $this->listeningQuestionService->getQuestionDetails($question);
            return ListeningQuestionControllerHelper::successResponse($questionDetails, 'Question retrieved successfully');
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to retrieve question', $e);
        }
    }

    /**
     * Update a question
     */
    public function update(UpdateQuestionRequest $request, ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            if (!ListeningQuestionControllerHelper::validateQuestionOwnership($question, $listeningTask)) {
                return response()->json(['status' => 'error', 'message' => 'Question not found'], Response::HTTP_NOT_FOUND);
            }

            $updatedQuestion = $this->listeningQuestionService->updateQuestion($question, $request->validated());
            return ListeningQuestionControllerHelper::successResponse($updatedQuestion, 'Question updated successfully');
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to update question', $e);
        }
    }

    /**
     * Delete a question
     */
    public function destroy(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            if (!ListeningQuestionControllerHelper::validateQuestionOwnership($question, $listeningTask)) {
                return response()->json(['status' => 'error', 'message' => 'Question not found'], Response::HTTP_NOT_FOUND);
            }

            $this->listeningQuestionService->deleteQuestion($question);
            return response()->json(['status' => 'success', 'message' => 'Question deleted successfully']);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to delete question', $e);
        }
    }

    /**
     * Get available question types for listening tasks
     */
    public function getQuestionTypes(): JsonResponse
    {
        try {
            $questionTypes = $this->listeningQuestionService->getAvailableQuestionTypes();
            $formattedTypes = ListeningQuestionControllerHelper::formatQuestionTypesResponse($questionTypes);
            
            return response()->json([
                'status' => 'success',
                'data' => $formattedTypes,
                'message' => 'Question types retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to retrieve question types', $e);
        }
    }

    /**
     * Bulk create questions from template
     */
    public function bulkCreate(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate(ListeningQuestionControllerHelper::validateBulkCreateRequest($request->all()));
            $results = $this->listeningQuestionService->bulkCreateQuestions($listeningTask, $request->questions);
            return ListeningQuestionControllerHelper::bulkOperationResponse($results);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to create questions', $e);
        }
    }

    /**
     * Reorder questions
     */
    public function reorder(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate(ListeningQuestionControllerHelper::validateReorderRequest());
            $this->listeningQuestionService->reorderQuestions($listeningTask, $request->question_orders);
            return response()->json(['status' => 'success', 'message' => 'Questions reordered successfully']);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to reorder questions', $e);
        }
    }

    /**
     * Preview question for students
     */
    public function preview(ListeningTask $listeningTask, TestQuestion $question): JsonResponse
    {
        try {
            if (!ListeningQuestionControllerHelper::validateQuestionOwnership($question, $listeningTask)) {
                return response()->json(['status' => 'error', 'message' => 'Question not found'], Response::HTTP_NOT_FOUND);
            }

            $preview = ListeningQuestionControllerHelper::processQuestionPreview($question, $this->listeningQuestionService);
            return response()->json(['status' => 'success', 'data' => $preview, 'message' => 'Question preview generated successfully']);
        } catch (\Exception $e) {
            return ListeningQuestionControllerHelper::errorResponse('Failed to generate preview', $e);
        }
    }
}