<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SpeakingTask\SpeakingDashboardResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingSubmissionResource;
use App\Models\Assignment;
use App\Models\SpeakingTask;
use App\Models\SpeakingSubmission;
use App\Models\SpeakingReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpeakingDashboardController extends Controller
{
    /**
     * Get speaking dashboard data for student
     */
    public function studentDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can access this dashboard'
            ], 403);
        }

        // 1. Basic Stats
        $tasksCompleted = SpeakingSubmission::where('student_id', $user->id)
            ->where('status', 'reviewed')
            ->count();

        $timeSpentSeconds = SpeakingSubmission::where('student_id', $user->id)
            ->sum('total_time_seconds');
        $timeSpentMinutes = round($timeSpentSeconds / 60, 1);

        $averageScoreQuery = SpeakingReview::whereHas('submission', function($q) use ($user) {
                $q->where('student_id', $user->id);
            });
        
        $averageScore = $averageScoreQuery->avg('total_score') ?? 0;

        // 2. Performance by Month (Last 6 months)
        $performanceByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthLabel = $monthDate->format('M');
            
            $avgMonthScore = SpeakingReview::whereHas('submission', function($q) use ($user) {
                    $q->where('student_id', $user->id);
                })
                ->whereMonth('reviewed_at', $monthDate->month)
                ->whereYear('reviewed_at', $monthDate->year)
                ->avg('total_score') ?? 0;

            $performanceByMonth[] = [
                'label' => $monthLabel,
                'average' => round((float)$avgMonthScore, 1)
            ];
        }

        // 3. Revision Insights (Categorized by Part 1, 2, 3)
        $submissions = SpeakingSubmission::where('student_id', $user->id)
            ->with('speakingTask:id,title')
            ->get();

        $insightsMap = [
            'Part 1' => ['count' => 0, 'unique_tasks' => []],
            'Part 2' => ['count' => 0, 'unique_tasks' => []],
            'Part 3' => ['count' => 0, 'unique_tasks' => []],
        ];

        foreach ($submissions as $sub) {
            $title = $sub->speakingTask->title ?? '';
            $part = 'Part 1'; // Default
            if (stripos($title, 'Part 2') !== false) $part = 'Part 2';
            elseif (stripos($title, 'Part 3') !== false) $part = 'Part 3';
            
            $insightsMap[$part]['count']++;
            $insightsMap[$part]['unique_tasks'][$sub->test_id] = true;
        }

        $revisionInsights = [];
        $index = 1;
        foreach ($insightsMap as $name => $data) {
            $uniqueCount = count($data['unique_tasks']);
            $avgRevision = $uniqueCount > 0 ? round($data['count'] / $uniqueCount, 1) : 0;
            
            $revisionInsights[] = [
                'id' => str_pad($index++, 2, '0', STR_PAD_LEFT),
                'name' => $name,
                'total_revision' => $data['count'],
                'avg_revision' => $avgRevision
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tasks_completed' => $tasksCompleted,
                'time_spent_minutes' => (float)$timeSpentMinutes,
                'average_score' => round((float)$averageScore, 1),
                'performance_by_month' => $performanceByMonth,
                'revision_insights' => $revisionInsights,
                'most_revised_task' => SpeakingSubmission::where('student_id', $user->id)
                    ->select('test_id', DB::raw('count(*) as count'))
                    ->groupBy('test_id')
                    ->orderBy('count', 'desc')
                    ->with('speakingTask:id,title')
                    ->first()?->speakingTask?->title ?? 'None'
            ]
        ]);
    }

    /**
     * Get teacher dashboard with speaking tasks and submissions to review
     */
    public function teacherDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!in_array($user->role, ['teacher', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers and admins can access this dashboard'
            ], 403);
        }

        $data = [];

        // Get teacher's speaking tasks
        $tasks = SpeakingTask::where('created_by', $user->id)
            ->when($request->task_status === 'published', fn($q) => $q->where('is_published', true))
            ->when($request->task_status === 'draft', fn($q) => $q->where('is_published', false))
            ->when($request->search, fn($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate($request->tasks_per_page ?? 10);

        $data['speaking_tasks'] = $tasks;

        // Get dashboard statistics
        $data['statistics'] = [
            'total_tasks' => SpeakingTask::where('created_by', $user->id)->count(),
            'published_tasks' => SpeakingTask::where('created_by', $user->id)
                ->where('is_published', true)
                ->count(),
            'pending_reviews' => SpeakingSubmission::whereHas('speakingTask', function ($q) use ($user) {
                    $q->where('created_by', $user->id);
                })
                ->where('status', 'submitted')
                ->count(),
            'completed_reviews' => SpeakingSubmission::whereHas('speakingTask', function ($q) use ($user) {
                    $q->where('created_by', $user->id);
                })
                ->where('status', 'reviewed')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get speaking task detail for student
     */
    public function getTaskDetail(string $assignmentId): JsonResponse
    {
        $user = auth()->user();

        $assignment = Assignment::where('id', $assignmentId)
            ->where('task_type', 'speaking_task')
            ->firstOrFail();

        $task = SpeakingTask::findOrFail($assignment->task_id);

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
                'task'       => $task,
            ]
        ]);
    }

    /**
     * Get class analytics
     */
    public function getClassAnalytics(string $classId): JsonResponse
    {
        // 1. Calculate Average Score for the class
        $averageScore = SpeakingReview::whereHas('submission.assignment', function($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->avg('total_score') ?? 0;

        // 2. Performance distribution
        $distribution = [
            'Excellent (90-100)' => SpeakingReview::whereHas('submission.assignment', fn($q) => $q->where('class_id', $classId))
                ->where('total_score', '>=', 90)->count(),
            'Good (70-89)' => SpeakingReview::whereHas('submission.assignment', fn($q) => $q->where('class_id', $classId))
                ->where('total_score', '>=', 70)->where('total_score', '<', 90)->count(),
            'Average (50-69)' => SpeakingReview::whereHas('submission.assignment', fn($q) => $q->where('class_id', $classId))
                ->where('total_score', '>=', 50)->where('total_score', '<', 70)->count(),
            'Needs Improvement (<50)' => SpeakingReview::whereHas('submission.assignment', fn($q) => $q->where('class_id', $classId))
                ->where('total_score', '<', 50)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'average_score' => round((float)$averageScore, 1),
                'distribution' => $distribution,
                'total_submissions' => SpeakingSubmission::whereHas('assignment', fn($q) => $q->where('class_id', $classId))->count()
            ]
        ]);
    }

    /**
     * Get individual student analytics for teacher
     */
    public function getStudentAnalytics(string $studentId): JsonResponse
    {
        $averageScore = SpeakingReview::whereHas('submission', function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->avg('total_score') ?? 0;

        $lastSubmissions = SpeakingSubmission::where('student_id', $studentId)
            ->with(['speakingTask:id,title', 'review:id,submission_id,total_score'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'average_score' => round((float)$averageScore, 1),
                'last_submissions' => $lastSubmissions,
                'total_completed' => SpeakingSubmission::where('student_id', $studentId)->where('status', 'reviewed')->count()
            ]
        ]);
    }
}