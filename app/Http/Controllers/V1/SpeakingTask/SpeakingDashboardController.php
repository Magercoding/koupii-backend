<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SpeakingTask\SpeakingDashboardResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingSubmissionResource;
use App\Models\SpeakingTask;
use App\Models\SpeakingTaskAssignment;
use App\Models\SpeakingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeakingDashboardController extends Controller
{
    /**
     * Get student dashboard with assigned speaking tasks
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

        // Get assigned speaking tasks for student's classes
        $assignments = SpeakingTaskAssignment::with([
            'speakingTask:id,title,description,difficulty_level,instructions,time_limit_seconds',
            'classroom:id,name',
        ])
        ->whereHas('classroom.students', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->latest()
        ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assignments,
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total()
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

        // Get teacher's speaking tasks using SpeakingTask model (not Test)
        $tasks = SpeakingTask::where('created_by', $user->id)
            ->when($request->task_status === 'published', fn($q) => $q->where('is_published', true))
            ->when($request->task_status === 'draft', fn($q) => $q->where('is_published', false))
            ->when($request->search, fn($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate($request->tasks_per_page ?? 10);

        $data['speaking_tasks'] = $tasks;

        // Get dashboard statistics using SpeakingTask model
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

        $assignment = SpeakingTaskAssignment::where('id', $assignmentId)
            ->whereHas('classroom.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'speakingTask',
                'classroom:id,name'
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
            ]
        ]);
    }
}