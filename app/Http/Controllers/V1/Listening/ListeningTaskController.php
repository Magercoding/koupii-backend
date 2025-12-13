<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\CreateListeningTaskRequest;
use App\Http\Requests\V1\Listening\UpdateListeningTaskRequest;
use App\Http\Resources\V1\Listening\ListeningTaskResource;
use App\Http\Resources\V1\Listening\ListeningTaskCollectionResource;
use App\Models\ListeningTask;
use App\Models\Test;
use App\Services\V1\Listening\ListeningTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningTaskController extends Controller
{
    public function __construct(
        private ListeningTaskService $listeningTaskService
    ) {}

    /**
     * Display a listing of listening tasks
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'test_id' => $request->get('test_id'),
                'task_type' => $request->get('task_type'),
                'difficulty_level' => $request->get('difficulty_level'),
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 10)
            ];

            $tasks = $this->listeningTaskService->getListeningTasks($filters);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningTaskCollectionResource($tasks),
                'message' => 'Listening tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve listening tasks: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created listening task
     */
    public function store(CreateListeningTaskRequest $request): JsonResponse
    {
        try {
            $taskData = $request->validated();
            $task = $this->listeningTaskService->createListeningTask($taskData);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningTaskResource($task),
                'message' => 'Listening task created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create listening task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified listening task
     */
    public function show(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $task = $this->listeningTaskService->getListeningTaskDetails($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningTaskResource($task),
                'message' => 'Listening task retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve listening task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified listening task
     */
    public function update(UpdateListeningTaskRequest $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $taskData = $request->validated();
            $task = $this->listeningTaskService->updateListeningTask($listeningTask, $taskData);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningTaskResource($task),
                'message' => 'Listening task updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update listening task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified listening task
     */
    public function destroy(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $this->listeningTaskService->deleteListeningTask($listeningTask);

            return response()->json([
                'status' => 'success',
                'message' => 'Listening task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete listening task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get listening tasks for a specific test
     */
    public function getByTest(Test $test): JsonResponse
    {
        try {
            $tasks = $this->listeningTaskService->getTasksByTest($test);

            return response()->json([
                'status' => 'success',
                'data' => ListeningTaskResource::collection($tasks),
                'message' => 'Test listening tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve test listening tasks: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}