<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingSubmission;
use App\Models\WritingReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingReviewService
{
    /**
     * Review student submission (teacher functionality).
     */
    public function reviewSubmission(WritingSubmission $submission, array $reviewData): WritingReview
    {
        return DB::transaction(function () use ($submission, $reviewData) {
            $review = WritingReview::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'teacher_id' => Auth::id(),
                'score' => $reviewData['score'] ?? null,
                'comments' => $reviewData['comments'] ?? null,
                'feedback_json' => $reviewData['feedback_json'] ?? null,
                'reviewed_at' => now(),
            ]);

            // Update submission status
            $submission->update(['status' => 'reviewed']);

            return $review;
        });
    }

    /**
     * Bulk review submissions.
     */
    public function bulkReview(array $reviews): array
    {
        $results = [];

        DB::transaction(function () use ($reviews, &$results) {
            foreach ($reviews as $reviewData) {
                try {
                    $submission = WritingSubmission::findOrFail($reviewData['submission_id']);

                    // Check authorization
                    if ($submission->writingTask->creator_id !== Auth::id() && Auth::user()->role !== 'admin') {
                        $results[] = [
                            'submission_id' => $reviewData['submission_id'],
                            'success' => false,
                            'error' => 'Unauthorized'
                        ];
                        continue;
                    }

                    $review = WritingReview::create([
                        'id' => Str::uuid(),
                        'submission_id' => $submission->id,
                        'teacher_id' => Auth::id(),
                        'score' => $reviewData['score'] ?? null,
                        'comments' => $reviewData['comments'] ?? null,
                        'feedback_json' => $reviewData['feedback_json'] ?? null,
                        'reviewed_at' => now(),
                    ]);

                    // Update submission status
                    $submission->update(['status' => 'reviewed']);

                    $results[] = [
                        'submission_id' => $reviewData['submission_id'],
                        'success' => true,
                        'review_id' => $review->id
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'submission_id' => $reviewData['submission_id'],
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Update existing review.
     */
    public function updateReview(WritingReview $review, array $data): WritingReview
    {
        $review->update([
            'score' => $data['score'] ?? $review->score,
            'comments' => $data['comments'] ?? $review->comments,
            'feedback_json' => $data['feedback_json'] ?? $review->feedback_json,
        ]);

        return $review;
    }

    /**
     * Get pending reviews for teacher.
     */
    public function getPendingReviews(string $teacherId = null): \Illuminate\Database\Eloquent\Collection
    {
        $teacherId = $teacherId ?? Auth::id();

        return WritingSubmission::with(['student', 'writingTask'])
            ->whereHas('writingTask', function ($query) use ($teacherId) {
                $query->where('creator_id', $teacherId);
            })
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'asc')
            ->get();
    }
}