<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WritingTask\ReviewSubmissionRequest;
use App\Http\Resources\V1\WritingTask\WritingSubmissionResource;
use App\Models\WritingSubmission;
use App\Services\V1\WritingTask\WritingReviewService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

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
     * Get pending reviews for teacher.
     */
    public function getPendingReviews(Request $request)
    {
        $teacherId = Auth::id();

        $pendingSubmissions = WritingSubmission::with(['student', 'writingTask'])
            ->whereHas('writingTask', function ($query) use ($teacherId) {
                $query->where('creator_id', $teacherId);
            })
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'asc')
            ->get();

        return response()->json([
            'message' => 'Pending reviews retrieved successfully',
            'data' => WritingSubmissionResource::collection($pendingSubmissions),
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
}