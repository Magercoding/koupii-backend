<?php

namespace App\Helpers\Listening;

use App\Http\Resources\V1\Listening\ListeningQuestionResource;
use App\Models\ListeningTask;
use App\Models\TestQuestion;
use App\Services\V1\Listening\ListeningQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ListeningQuestionControllerHelper
{
    /**
     * Handle successful response with resource transformation
     */
    public static function successResponse($data, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        $responseData = $data;
        
        if ($data instanceof TestQuestion) {
            $responseData = new ListeningQuestionResource($data);
        } elseif (is_iterable($data)) {
            $responseData = ListeningQuestionResource::collection($data);
        }

        return response()->json([
            'status' => 'success',
            'data' => $responseData,
            'message' => $message
        ], $status);
    }

    /**
     * Handle error response
     */
    public static function errorResponse(string $message, \Exception $e, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message . ': ' . $e->getMessage()
        ], $status);
    }

    /**
     * Handle bulk operations response
     */
    public static function bulkOperationResponse(array $results): JsonResponse
    {
        $hasErrors = !empty($results['errors']);
        $status = $hasErrors ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_CREATED;
        
        $message = $hasErrors 
            ? sprintf('Bulk operation completed with %d successes and %d errors', $results['created_count'], $results['error_count'])
            : sprintf('All %d operations completed successfully', $results['created_count']);

        return response()->json([
            'status' => $hasErrors ? 'partial_success' : 'success',
            'data' => [
                'created' => ListeningQuestionResource::collection($results['created']),
                'errors' => $results['errors'],
                'statistics' => [
                    'total_attempted' => $results['created_count'] + $results['error_count'],
                    'successful' => $results['created_count'],
                    'failed' => $results['error_count']
                ]
            ],
            'message' => $message
        ], $status);
    }

    /**
     * Validate bulk creation request
     */
    public static function validateBulkCreateRequest(array $questions): array
    {
        $rules = [
            'questions' => 'required|array|min:1|max:50',
            'questions.*.question_type' => 'required|string',
            'questions.*.question_text' => 'required|string|max:2000',
            'questions.*.points' => 'nullable|numeric|min:0|max:100',
            'questions.*.options' => 'nullable|array',
            'questions.*.question_data' => 'nullable|array',
            'questions.*.instructions' => 'nullable|string|max:1000',
            'questions.*.explanation' => 'nullable|string|max:2000'
        ];

        return $rules;
    }

    /**
     * Validate reorder request
     */
    public static function validateReorderRequest(): array
    {
        return [
            'question_orders' => 'required|array|min:1',
            'question_orders.*.question_id' => 'required|string|exists:test_questions,id',
            'question_orders.*.order' => 'required|integer|min:1'
        ];
    }

    /**
     * Process question preview with security filtering
     */
    public static function processQuestionPreview(TestQuestion $question, ListeningQuestionService $service): array
    {
        $preview = $service->getQuestionPreview($question);
        
        // Remove sensitive data for preview
        if (isset($preview['question'])) {
            unset($preview['question']['correct_answer']);
            unset($preview['question']['answer_explanation']);
        }

        return $preview;
    }

    /**
     * Format question types response
     */
    public static function formatQuestionTypesResponse(array $questionTypes): array
    {
        return array_map(function ($type) {
            return [
                'type' => $type['type'],
                'name' => $type['name'],
                'description' => $type['description'] ?? '',
                'supports_options' => $type['supports_options'] ?? true,
                'supports_audio' => $type['supports_audio'] ?? true,
                'example_template' => $type['template'] ?? []
            ];
        }, $questionTypes);
    }

    /**
     * Validate question ownership
     */
    public static function validateQuestionOwnership(TestQuestion $question, ListeningTask $listeningTask): bool
    {
        return $question->test_id === $listeningTask->test_id;
    }
}