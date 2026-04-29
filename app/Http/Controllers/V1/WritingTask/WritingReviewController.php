<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WritingTask\ReviewSubmissionRequest;
use App\Http\Resources\V1\WritingTask\WritingSubmissionResource;
use App\Models\WritingSubmission;
use App\Models\StudentAssignment;
use App\Models\WritingReview;
use App\Services\V1\WritingTask\WritingReviewService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WritingReviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Review a student submission.
     */
    public function review(ReviewSubmissionRequest $request, string $taskId, string $submissionId)
    {
        $submission = WritingSubmission::findOrFail($submissionId);

        // Check authorization
        if (Auth::user()->role !== 'admin' && $submission->writingTask->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new WritingReviewService();
            $review = $service->reviewSubmission($submission, $request->validated());

            return response()->json([
                'message' => 'Submission reviewed successfully',
                'data' => [
                    'submission' => new WritingSubmissionResource($submission->load('review')),
                    'review' => $review,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to review submission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reviews for teacher (pending or graded).
     */
    public function getReviews(Request $request)
    {
        $teacherId = Auth::id();
        $status = $request->query('status', 'submitted');
        $classId = $request->query('class_id');

        $query = StudentAssignment::with(['student', 'writingTask', 'assignment.class'])
            ->whereHas('writingTask', function ($query) use ($teacherId) {
                $query->where('creator_id', $teacherId);
            })
            ->where('status', $status)
            ->orderBy('updated_at', 'desc');

        if ($classId && $classId !== 'all') {
            $query->whereHas('assignment', function ($q) use ($classId, $teacherId) {
                $q->where('class_id', $classId)
                  ->whereHas('class', function($cq) use ($teacherId) {
                      $cq->where('teacher_id', $teacherId);
                  });
            });
        }

        $assignments = $query->get();

        // Get list of teacher's classes for the filter
        $classes = \App\Models\Classes::where('teacher_id', $teacherId)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'message' => 'Assignment reviews retrieved successfully',
            'data' => $assignments,
            'meta' => [
                'classes' => $classes
            ]
        ], 200);
    }

    /**
     * Bulk review submissions.
     */
    public function bulkReview(Request $request)
    {
        $request->validate([
            'reviews' => 'required|array|min:1',
            'reviews.*.submission_id' => 'required|exists:writing_submissions,id',
            'reviews.*.score' => 'nullable|integer|min:0|max:100',
            'reviews.*.comments' => 'nullable|string',
        ]);

        try {
            $service = new WritingReviewService();
            $results = $service->bulkReview($request->reviews);

            return response()->json([
                'message' => 'Bulk review completed successfully',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete bulk review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail of a student assignment for review.
     */
    public function showAssignment(string $assignmentId)
    {
        $assignment = StudentAssignment::with([
            'student',
            'assignment',
            'writingTask.taskQuestions',
        ])->findOrFail($assignmentId);

        // Check auth (only creator of the task or admin)
        if (Auth::user()->role !== 'admin' && $assignment->writingTask?->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load writing submissions separately to avoid the dynamic $this->student_id issue
        $submissions = WritingSubmission::with('latestReview')
            ->where('assignment_id', $assignment->assignment_id)
            ->where('student_id', $assignment->student_id)
            ->orderBy('created_at', 'asc')
            ->get();

        $data = $assignment->toArray();
        $data['writingSubmissions'] = $submissions->toArray();

        // If writingTask didn't load via hasOneThrough, try direct lookup
        if (empty($data['writing_task']) && $assignment->assignment) {
            $taskId = $assignment->assignment->task_id;
            if ($taskId) {
                $writingTask = \App\Models\WritingTask::with('taskQuestions')->find($taskId);
                if ($writingTask) {
                    $data['writing_task'] = $writingTask->toArray();
                }
            }
        }

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Submit review for an entire assignment (possibly multiple submissions).
     */
    public function submitAssignmentReview(Request $request, string $assignmentId)
    {
        $request->validate([
            'overall_score' => 'required|numeric|min:0|max:100',
            'overall_comments' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.submission_id' => 'required|exists:writing_submissions,id',
            'items.*.score' => 'required|numeric|min:0|max:100',
            'items.*.comments' => 'nullable|string',
            'feedback_json' => 'nullable|array',
        ]);

        $assignment = StudentAssignment::findOrFail($assignmentId);

        // Check auth
        if (Auth::user()->role !== 'admin' && $assignment->writingTask->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::transaction(function () use ($assignment, $request) {
                // 1. Update Student Assignment (Overall Result)
                $assignment->update([
                    'score' => $request->overall_score,
                    'status' => 'graded',
                    'completed_at' => now(),
                ]);

                // 2. Process each submission review
                foreach ($request->items as $item) {
                    WritingReview::updateOrCreate(
                        [
                            'assignment_id' => $assignment->id,
                            'submission_id' => $item['submission_id']
                        ],
                        [
                            'teacher_id' => Auth::id(),
                            'score' => $item['score'],
                            'comments' => $item['comments'] ?? '',
                            'feedback_json' => $request->feedback_json, // Global for now or per item? Keep global for schema
                            'reviewed_at' => now(),
                        ]
                    );

                    // Update individual submission status
                    WritingSubmission::where('id', $item['submission_id'])->update(['status' => 'reviewed']);
                }
            });

            // Notify the student
            $assignment->student->notify(new \App\Notifications\TaskGradedNotification($assignment));

            return response()->json([
                'message' => 'Assignment and all parts reviewed successfully',
                'status' => 'graded'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}