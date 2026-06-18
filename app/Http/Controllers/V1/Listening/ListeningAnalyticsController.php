<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Listening\ListeningAnalyticsResource;
use App\Models\ListeningTask;
use App\Models\Test;
use App\Models\User;
use App\Services\V1\Listening\ListeningAnalyticsService;
use App\Services\V1\Test\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ListeningAnalyticsController extends Controller
{
    public function __construct(
        private ListeningAnalyticsService $listeningAnalyticsService,
        private TestService $testService,
    ) {}

    /**
     * Get analytics for a specific listening task
     */
    public function getTaskAnalytics(Request $request, string $id): JsonResponse
    {
        try {
            $task = $this->testService->findAnyTaskById($id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Listening task not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $taskType = $task instanceof ListeningTask ? 'listening' : ($task->type ?? null);
            if ($taskType !== 'listening') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task is not a listening test',
                ], Response::HTTP_NOT_FOUND);
            }

            $user = Auth::user();
            $ownerId = $task instanceof ListeningTask ? $task->created_by : $task->creator_id;

            if ($user->role !== 'admin' && $ownerId !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this task report',
                ], Response::HTTP_FORBIDDEN);
            }

            $analytics = $this->listeningAnalyticsService->getTaskAnalytics($task, $request);

            return response()->json([
                'status' => 'success',
                'data' => new ListeningAnalyticsResource($analytics),
                'message' => 'Task analytics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve task analytics: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get student performance analytics
     */
    public function getStudentAnalytics(Request $request, User $student): JsonResponse
    {
        try {
            $filters = [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'task_type' => $request->get('task_type'),
                'difficulty_level' => $request->get('difficulty_level')
            ];

            $analytics = $this->listeningAnalyticsService->getStudentPerformanceAnalytics($student, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Student analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve student analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get question type performance analytics
     */
    public function getQuestionTypeAnalytics(Request $request): JsonResponse
    {
        try {
            $filters = [
                'test_id' => $request->get('test_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'student_ids' => $request->get('student_ids', [])
            ];

            $analytics = $this->listeningAnalyticsService->getQuestionTypeAnalytics($filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Question type analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve question type analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get audio interaction analytics
     */
    public function getAudioAnalytics(Request $request): JsonResponse
    {
        try {
            $filters = [
                'task_id' => $request->get('task_id'),
                'student_id' => $request->get('student_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to')
            ];

            $analytics = $this->listeningAnalyticsService->getAudioInteractionAnalytics($filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Audio analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get progress tracking analytics
     */
    public function getProgressAnalytics(Request $request, User $student): JsonResponse
    {
        try {
            $timeframe = $request->get('timeframe', '30days');

            $analytics = $this->listeningAnalyticsService->getProgressAnalytics($student, $timeframe);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Progress analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve progress analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get comparative analytics between students
     */
    public function getComparativeAnalytics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:2|max:10',
                'student_ids.*' => 'string|exists:users,id',
                'metric' => 'nullable|string|in:accuracy,completion_time,audio_plays,improvement_rate',
                'task_type' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after:date_from'
            ]);

            $analytics = $this->listeningAnalyticsService->getComparativeAnalytics($request->validated());

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Comparative analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve comparative analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate detailed report
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'report_type' => 'required|string|in:task_performance,student_progress,class_overview,question_analysis',
                'task_id' => 'nullable|string|exists:listening_tasks,id',
                'student_id' => 'nullable|string|exists:users,id',
                'class_id' => 'nullable|string|exists:classes,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after:date_from',
                'format' => 'nullable|string|in:json,pdf,excel'
            ]);

            $report = $this->listeningAnalyticsService->generateReport($request->validated());

            return response()->json([
                'status' => 'success',
                'data' => $report,
                'message' => 'Report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get real-time analytics dashboard data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            $filters = [
                'teacher_id' => auth()->id(),
                'class_id' => $request->get('class_id'),
                'month' => $request->get('month'),
                'timeframe' => $request->get('timeframe', 'week'),
                'include_inactive' => $request->boolean('include_inactive', false)
            ];

            $dashboardData = $this->listeningAnalyticsService->getDashboardData($filters);

            return response()->json([
                'status' => 'success',
                'data' => $dashboardData,
                'message' => 'Dashboard data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'export_type' => 'required|string|in:csv,excel,json',
                'data_type' => 'required|string|in:submissions,audio_logs,performance_metrics',
                'filters' => 'nullable|array'
            ]);

            $exportData = $this->listeningAnalyticsService->exportData($request->validated());

            return response()->json([
                'status' => 'success',
                'data' => $exportData,
                'message' => 'Data exported successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}