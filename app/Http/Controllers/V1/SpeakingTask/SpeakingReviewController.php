<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SpeakingTask\ReviewSpeakingRequest;
use App\Http\Resources\V1\SpeakingTask\SpeakingReviewResource;
use App\Models\SpeakingSubmission;
use App\Models\SpeakingReview;
use App\Services\V1\SpeakingTask\SpeakingSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpeakingReviewController extends Controller
{
    public function __construct(
        private SpeakingSubmissionService $speakingSubmissionService
    ) {
    }

    /**
     * Store a new speaking review
     */
    public function store(ReviewSpeakingRequest $request): JsonResponse
    {
        $submission = SpeakingSubmission::findOrFail($request->submission_id);
        
        // Gate::authorize('review', $submission); // Ensure gate is defined

        $review = $this->speakingSubmissionService->reviewSubmission(
            $submission,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => new SpeakingReviewResource($review)
        ]);
    }

    /**
     * Display the specified speaking review
     */
    public function show(SpeakingReview $review): JsonResponse
    {
        // Gate::authorize('view', $review);

        return response()->json([
            'success' => true,
            'data' => new SpeakingReviewResource($review->load(['teacher:id,name', 'submission.student:id,name']))
        ]);
    }

    /**
     * Update the specified speaking review
     */
    public function update(ReviewSpeakingRequest $request, SpeakingReview $review): JsonResponse
    {
        // Gate::authorize('update', $review);

        $submission = $review->submission;
        
        $review = $this->speakingSubmissionService->reviewSubmission(
            $submission,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => new SpeakingReviewResource($review)
        ]);
    }

    /**
     * Delete a review (Soft delete if needed)
     */
    public function destroy(SpeakingReview $review): JsonResponse
    {
        // Gate::authorize('delete', $review);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}
