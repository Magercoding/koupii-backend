<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\ReviewListeningRequest;
use App\Http\Resources\V1\Listening\ListeningReviewResource;
use App\Models\ListeningSubmission;
use App\Models\ListeningReview;
use App\Services\V1\Listening\ListeningReviewService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ListeningReviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Create a review for a submission.
     */
    public function store(ReviewListeningRequest $request, string $submissionId)
    {
        $submission = ListeningSubmission::with('task')->findOrFail($submissionId);

        // Check authorization - only task creator or admin can review
        if (Auth::user()->role !== 'admin' && $submission->task->creator_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if submission is in correct state
        if ($submission->status !== 'submitted') {
            return response()->json([
                'message' => 'Submission must be in submitted status to be reviewed'
            ], 400);
        }

        try {
            $service = new ListeningReviewService();
            $review = $service->createReview($submission, Auth::user(), $request->validated());

            return response()->json([
                'message' => 'Review created successfully',
                'data' => new ListeningReviewResource($review),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get review details.
     */
    public function show(Request $request, string $reviewId)
    {
        $review = ListeningReview::with(['submission.task', 'submission.student', 'reviewer'])
            ->findOrFail($reviewId);

        $user = Auth::user();

        // Check authorization
        if ($user->role === 'student' && $review->submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'teacher' && $review->submission->task->creator_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Review retrieved successfully',
            'data' => new ListeningReviewResource($review),
        ], 200);
    }

    /**
     * Update an existing review.
     */
    public function update(ReviewListeningRequest $request, string $reviewId)
    {
        $review = ListeningReview::with('submission.task')->findOrFail($reviewId);

        // Check authorization - only original reviewer or admin can update
        if (Auth::user()->role !== 'admin' && $review->reviewer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new ListeningReviewService();
            $updatedReview = $service->updateReview($review, $request->validated());

            return response()->json([
                'message' => 'Review updated successfully',
                'data' => new ListeningReviewResource($updatedReview),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review.
     */
    public function destroy(string $reviewId)
    {
        $review = ListeningReview::with('submission.task')->findOrFail($reviewId);

        // Check authorization - only original reviewer or admin can delete
        if (Auth::user()->role !== 'admin' && $review->reviewer_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new ListeningReviewService();
            $service->deleteReview($review);

            return response()->json([
                'message' => 'Review deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all reviews for a submission.
     */
    public function getSubmissionReviews(Request $request, string $submissionId)
    {
        $submission = ListeningSubmission::with('task')->findOrFail($submissionId);
        $user = Auth::user();

        // Check authorization
        if ($user->role === 'student' && $submission->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($user->role === 'teacher' && $submission->task->creator_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reviews = ListeningReview::with('reviewer')
            ->where('submission_id', $submissionId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Reviews retrieved successfully',
            'data' => ListeningReviewResource::collection($reviews),
        ], 200);
    }
}