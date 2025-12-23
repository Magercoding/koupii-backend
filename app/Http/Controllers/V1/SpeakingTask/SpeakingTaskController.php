<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SpeakingTask\StoreSpeakingTaskRequest;
use App\Http\Requests\V1\SpeakingTask\UpdateSpeakingTaskRequest;
use App\Http\Resources\V1\SpeakingTask\SpeakingTaskResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingTaskCollection;
use App\Models\Test;
use App\Services\V1\SpeakingTask\SpeakingTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpeakingTaskController extends Controller
{
    public function __construct(
        private SpeakingTaskService $speakingTaskService
    ) {}

    /**
     * Display a listing of speaking tasks
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'difficulty' => $request->get('difficulty'),
                'is_published' => $request->get('is_published'),
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 15)
            ];

            $tasks = $this->speakingTaskService->getSpeakingTasks($filters);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskCollection($tasks),
                'message' => 'Speaking tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve speaking tasks: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created speaking task
     */
    public function store(StoreSpeakingTaskRequest $request): JsonResponse
    {
        try {
            $taskData = $request->validated();
            $task = $this->speakingTaskService->createSpeakingTask($taskData);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($task),
                'message' => 'Speaking task created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified speaking task
     */
    public function show(Test $speakingTask): JsonResponse
    {
        try {
            $task = $this->speakingTaskService->getSpeakingTaskDetails($speakingTask);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($task),
                'message' => 'Speaking task retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified speaking task
     */
    public function update(UpdateSpeakingTaskRequest $request, Test $speakingTask): JsonResponse
    {
        try {
            $taskData = $request->validated();
            $task = $this->speakingTaskService->updateSpeakingTask($speakingTask, $taskData);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($task),
                'message' => 'Speaking task updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified speaking task
     */
    public function destroy(Test $speakingTask): JsonResponse
    {
        try {
            $this->speakingTaskService->deleteSpeakingTask($speakingTask);

            return response()->json([
                'status' => 'success',
                'message' => 'Speaking task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate a speaking task
     */
    public function duplicate(Request $request, Test $speakingTask): JsonResponse
    {
        try {
            $duplicateData = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            $duplicatedTask = $this->speakingTaskService->duplicateSpeakingTask($speakingTask, $duplicateData);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($duplicatedTask),
                'message' => 'Speaking task duplicated successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to duplicate speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Publish a speaking task
     */
    public function publish(Test $speakingTask): JsonResponse
    {
        try {
            $publishedTask = $this->speakingTaskService->publishSpeakingTask($speakingTask);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($publishedTask),
                'message' => 'Speaking task published successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to publish speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unpublish a speaking task
     */
    public function unpublish(Test $speakingTask): JsonResponse
    {
        try {
            $unpublishedTask = $this->speakingTaskService->unpublishSpeakingTask($speakingTask);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingTaskResource($unpublishedTask),
                'message' => 'Speaking task unpublished successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unpublish speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign a speaking task to students/classes
     */
    public function assign(Request $request, Test $speakingTask): JsonResponse
    {
        try {
            $assignmentData = $request->validate([
                'assignment_type' => 'required|string|in:class,individual',
                'class_ids' => 'nullable|array',
                'class_ids.*' => 'uuid|exists:classes,id',
                'student_ids' => 'nullable|array',
                'student_ids.*' => 'uuid|exists:users,id',
                'due_date' => 'nullable|date|after:now',
                'allow_retake' => 'boolean',
                'max_attempts' => 'nullable|integer|min:1|max:5',
            ]);

            $assignmentResult = $this->speakingTaskService->assignSpeakingTask($speakingTask, $assignmentData);

            return response()->json([
                'status' => 'success',
                'data' => $assignmentResult,
                'message' => 'Speaking task assigned successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign speaking task: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}