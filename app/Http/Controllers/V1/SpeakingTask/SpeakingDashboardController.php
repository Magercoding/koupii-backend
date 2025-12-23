<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SpeakingTask\SpeakingDashboardResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingSubmissionResource;
use App\Services\V1\SpeakingTask\SpeakingSubmissionService;
use App\Models\SpeakingTaskAssignment;
use App\Models\SpeakingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeakingDashboardController extends Controller
{
    public function __construct(
        private SpeakingSubmissionService $speakingSubmissionService
    ) {
    }

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
            'test:id,title,description,difficulty,instructions,timer_type,time_limit_seconds',
            'class:id,name',
            'assignedBy:id,name'
        ])
        ->whereHas('class.students', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->when($request->status, function ($query, $status) {
            if ($status === 'to_do') {
                $query->whereDoesntHave('test.speakingSubmissions', function ($q) {
                    $q->where('student_id', auth()->id());
                });
            } elseif ($status === 'submitted') {
                $query->whereHas('test.speakingSubmissions', function ($q) {
                    $q->where('student_id', auth()->id())
                      ->where('status', 'submitted');
                });
            } elseif ($status === 'reviewed') {
                $query->whereHas('test.speakingSubmissions', function ($q) {
                    $q->where('student_id', auth()->id())
                      ->where('status', 'reviewed');
                });
            }
        })
        ->latest()
        ->paginate(15);

        // Add submission status for each assignment
        $assignments->getCollection()->transform(function ($assignment) use ($user) {
            $submission = SpeakingSubmission::where('test_id', $assignment->test_id)
                ->where('student_id', $user->id)
                ->with('review:id,submission_id,overall_score,feedback,reviewed_at')
                ->latest()
                ->first();

            $assignment->submission_status = $submission ? $submission->status : 'to_do';
            $assignment->latest_submission = $submission;
            
            return $assignment;
        });

        return response()->json([
            'success' => true,
            'data' => SpeakingDashboardResource::collection($assignments),
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

        // Get teacher's speaking tasks
        $tasks = $this->speakingSubmissionService->getTeacherSpeakingTasks($user->id, [
            'status' => $request->task_status,
            'search' => $request->search,
            'per_page' => $request->tasks_per_page ?? 10
        ]);

        $data['speaking_tasks'] = $tasks;

        // Get submissions waiting for review
        $reviewQueue = $this->speakingSubmissionService->getTeacherReviewQueue($user->id, [
            'test_id' => $request->test_id,
            'per_page' => $request->reviews_per_page ?? 10
        ]);

        $data['review_queue'] = SpeakingSubmissionResource::collection($reviewQueue);

        // Get dashboard statistics
        $data['statistics'] = [
            'total_tasks' => \App\Models\Test::where('creator_id', $user->id)
                ->where('test_type', 'speaking')
                ->count(),
            'published_tasks' => \App\Models\Test::where('creator_id', $user->id)
                ->where('test_type', 'speaking')
                ->where('is_published', true)
                ->count(),
            'pending_reviews' => SpeakingSubmission::whereHas('test', function ($q) use ($user) {
                    $q->where('creator_id', $user->id);
                })
                ->where('status', 'submitted')
                ->count(),
            'completed_reviews' => SpeakingSubmission::whereHas('test', function ($q) use ($user) {
                    $q->where('creator_id', $user->id);
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
    public function getTaskDetail(string $testId): JsonResponse
    {
        $user = auth()->user();

        // Check if student has access to this test
        $assignment = SpeakingTaskAssignment::where('test_id', $testId)
            ->whereHas('class.students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'test.speakingSections.topics.questions',
                'class:id,name'
            ])
            ->firstOrFail();

        // Get student's latest submission if exists
        $submission = SpeakingSubmission::where('test_id', $testId)
            ->where('student_id', $user->id)
            ->with(['recordings', 'review'])
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => new SpeakingDashboardResource($assignment),
                'submission' => $submission ? new SpeakingSubmissionResource($submission) : null
            ]
        ]);
    }
}