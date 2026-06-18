<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ReadingTest\ReadingAnalyticsResource;
use App\Models\ReadingTask;
use App\Models\Test;
use App\Services\V1\ReadingTest\ReadingAnalyticsService;
use App\Services\V1\Test\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReadingAnalyticsController extends Controller
{
    public function __construct(
        private ReadingAnalyticsService $readingAnalyticsService,
        private TestService $testService,
    ) {}

    public function getTaskAnalytics(Request $request, string $id): JsonResponse
    {
        try {
            $task = $this->testService->findAnyTaskById($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reading task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $taskType = $task->type ?? $task->task_type ?? null;
            if ($task instanceof ReadingTask) {
                $taskType = 'reading';
            }

            if ($taskType !== 'reading') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task is not a reading test',
                ], Response::HTTP_NOT_FOUND);
            }

            $user = Auth::user();
            $ownerId = $task instanceof ReadingTask ? $task->created_by : $task->creator_id;

            if ($user->role !== 'admin' && $ownerId !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this task report',
                ], Response::HTTP_FORBIDDEN);
            }

            $analytics = $this->readingAnalyticsService->getTaskAnalytics($task, $request);

            return response()->json([
                'status' => 'success',
                'data' => new ReadingAnalyticsResource($analytics),
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
