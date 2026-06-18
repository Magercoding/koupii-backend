<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SpeakingTask\SpeakingAnalyticsResource;
use App\Models\SpeakingTask;
use App\Models\Test;
use App\Services\V1\SpeakingTask\SpeakingAnalyticsService;
use App\Services\V1\Test\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SpeakingAnalyticsController extends Controller
{
    public function __construct(
        private SpeakingAnalyticsService $speakingAnalyticsService,
        private TestService $testService,
    ) {}

    public function getTaskAnalytics(Request $request, string $id): JsonResponse
    {
        try {
            $task = $this->testService->findAnyTaskById($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Speaking task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $taskType = $task instanceof SpeakingTask ? 'speaking' : ($task->type ?? null);
            if ($taskType !== 'speaking') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task is not a speaking test',
                ], Response::HTTP_NOT_FOUND);
            }

            $user = Auth::user();
            $ownerId = $task instanceof SpeakingTask ? $task->created_by : $task->creator_id;

            if ($user->role !== 'admin' && $ownerId !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this task report',
                ], Response::HTTP_FORBIDDEN);
            }

            $analytics = $this->speakingAnalyticsService->getTaskAnalytics($task, $request);

            return response()->json([
                'status' => 'success',
                'data' => new SpeakingAnalyticsResource($analytics),
                'message' => 'Task analytics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve task analytics: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
