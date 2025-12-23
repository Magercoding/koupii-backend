<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningSubmission;
use App\Models\ListeningReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListeningReviewService
{
    /**
     * Create a review for a submission.
     */
    public function createReview(ListeningSubmission $submission, User $reviewer, array $reviewData): ListeningReview
    {
        return DB::transaction(function () use ($submission, $reviewer, $reviewData) {
            $review = ListeningReview::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'teacher_id' => $reviewer->id,
                'score' => $reviewData['score'] ?? null,
                'comments' => $reviewData['comments'] ?? null,
                'feedback_json' => $reviewData['feedback_json'] ?? null,
                'reviewed_at' => now(),
            ]);

            // Update submission status to reviewed
            $submission->update(['status' => 'reviewed']);

            return $review->fresh(['submission.task', 'submission.student', 'teacher']);
        });
    }

    /**
     * Update an existing review.
     */
    public function updateReview(ListeningReview $review, array $reviewData): ListeningReview
    {
        return DB::transaction(function () use ($review, $reviewData) {
            $review->update([
                'score' => $reviewData['score'] ?? $review->score,
                'comments' => $reviewData['comments'] ?? $review->comments,
                'feedback_json' => $reviewData['feedback_json'] ?? $review->feedback_json,
                'reviewed_at' => now(),
            ]);

            return $review->fresh(['submission.task', 'submission.student', 'teacher']);
        });
    }

    /**
     * Delete a review.
     */
    public function deleteReview(ListeningReview $review): bool
    {
        return DB::transaction(function () use ($review) {
            // Update submission status back to submitted
            $review->submission->update(['status' => 'submitted']);

            // Delete the review
            return $review->delete();
        });
    }

    /**
     * Bulk review multiple submissions.
     */
    public function bulkReview(array $reviews, User $reviewer): array
    {
        $results = [];

        DB::transaction(function () use ($reviews, $reviewer, &$results) {
            foreach ($reviews as $reviewData) {
                try {
                    $submission = ListeningSubmission::with('task')->findOrFail($reviewData['submission_id']);

                    // Check authorization
                    if ($submission->task->creator_id !== $reviewer->id && $reviewer->role !== 'admin') {
                        $results[] = [
                            'submission_id' => $reviewData['submission_id'],
                            'success' => false,
                            'error' => 'Unauthorized to review this submission',
                        ];
                        continue;
                    }

                    // Check if submission can be reviewed
                    if ($submission->status !== 'submitted') {
                        $results[] = [
                            'submission_id' => $reviewData['submission_id'],
                            'success' => false,
                            'error' => 'Submission is not in submitted status',
                        ];
                        continue;
                    }

                    $review = $this->createReview($submission, $reviewer, $reviewData);

                    $results[] = [
                        'submission_id' => $reviewData['submission_id'],
                        'success' => true,
                        'review_id' => $review->id,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'submission_id' => $reviewData['submission_id'] ?? null,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Get review statistics for a task.
     */
    public function getReviewStats(string $taskId): array
    {
        $submissions = ListeningSubmission::where('listening_task_id', $taskId)->get();
        $reviews = ListeningReview::whereIn('submission_id', $submissions->pluck('id'))->get();

        return [
            'total_submissions' => $submissions->count(),
            'reviewed_submissions' => $reviews->count(),
            'pending_review' => $submissions->where('status', 'submitted')->count(),
            'average_score' => $reviews->whereNotNull('score')->avg('score'),
            'highest_score' => $reviews->max('score'),
            'lowest_score' => $reviews->where('score', '>', 0)->min('score'),
            'review_completion_rate' => $submissions->count() > 0 ? 
                round(($reviews->count() / $submissions->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Auto-score submission based on correct answers.
     */
    public function autoScoreSubmission(ListeningSubmission $submission): array
    {
        $answers = $submission->answers;
        $totalQuestions = $answers->count();
        
        if ($totalQuestions === 0) {
            return [
                'total_score' => 0,
                'percentage' => 0,
                'breakdown' => []
            ];
        }

        $correctCount = $answers->where('is_correct', true)->count();
        $incorrectCount = $answers->where('is_correct', false)->count();
        $unansweredCount = $answers->whereNull('is_correct')->count();

        $percentage = ($correctCount / $totalQuestions) * 100;
        $taskPoints = $submission->task->points ?? 100;
        $totalScore = ($correctCount / $totalQuestions) * $taskPoints;

        return [
            'total_score' => round($totalScore, 2),
            'percentage' => round($percentage, 2),
            'breakdown' => [
                'correct' => $correctCount,
                'incorrect' => $incorrectCount,
                'unanswered' => $unansweredCount,
                'total_questions' => $totalQuestions,
            ]
        ];
    }

    /**
     * Provide feedback suggestions based on submission performance.
     */
    public function generateFeedbackSuggestions(ListeningSubmission $submission): array
    {
        $scoreData = $this->autoScoreSubmission($submission);
        $percentage = $scoreData['percentage'];
        
        $suggestions = [];

        if ($percentage >= 90) {
            $suggestions[] = "Excellent listening comprehension! Your understanding of the audio content is outstanding.";
        } elseif ($percentage >= 80) {
            $suggestions[] = "Great job! Your listening skills are very good with minor areas for improvement.";
        } elseif ($percentage >= 70) {
            $suggestions[] = "Good listening comprehension. Consider practicing with more complex audio materials.";
        } elseif ($percentage >= 60) {
            $suggestions[] = "Satisfactory performance. Focus on improving attention to detail in listening exercises.";
        } else {
            $suggestions[] = "More practice needed. Try listening to simpler materials first and gradually increase complexity.";
        }

        // Analyze specific weak areas
        $answers = $submission->answers;
        $questionTypes = $answers->groupBy('question.question_type');

        foreach ($questionTypes as $type => $typeAnswers) {
            $typeCorrect = $typeAnswers->where('is_correct', true)->count();
            $typeTotal = $typeAnswers->count();
            $typePercentage = ($typeCorrect / $typeTotal) * 100;

            if ($typePercentage < 60) {
                switch ($type) {
                    case 'multiple_choice':
                        $suggestions[] = "Focus on improving multiple choice question strategies.";
                        break;
                    case 'fill_blank':
                        $suggestions[] = "Practice more fill-in-the-blank exercises to improve word recognition.";
                        break;
                    case 'true_false':
                        $suggestions[] = "Work on distinguishing between true and false statements in audio content.";
                        break;
                    default:
                        $suggestions[] = "Review {$type} question types for better performance.";
                }
            }
        }

        return [
            'suggestions' => $suggestions,
            'performance_level' => $this->getPerformanceLevel($percentage),
            'areas_for_improvement' => $this->getImprovementAreas($submission),
        ];
    }

    /**
     * Get performance level based on percentage.
     */
    private function getPerformanceLevel(float $percentage): string
    {
        if ($percentage >= 90) return 'Excellent';
        if ($percentage >= 80) return 'Very Good';
        if ($percentage >= 70) return 'Good';
        if ($percentage >= 60) return 'Satisfactory';
        return 'Needs Improvement';
    }

    /**
     * Get areas for improvement based on submission analysis.
     */
    private function getImprovementAreas(ListeningSubmission $submission): array
    {
        $areas = [];
        $answers = $submission->answers;

        // Analyze audio play counts
        if ($submission->audio_play_counts) {
            $avgPlayCount = array_sum($submission->audio_play_counts) / count($submission->audio_play_counts);
            if ($avgPlayCount > 3) {
                $areas[] = 'Audio comprehension - try to understand content with fewer replays';
            }
        }

        // Analyze time taken
        if ($submission->time_taken_seconds && $submission->task->time_limit) {
            $timeRatio = $submission->time_taken_seconds / ($submission->task->time_limit * 60);
            if ($timeRatio > 0.9) {
                $areas[] = 'Time management - work on completing tasks more efficiently';
            }
        }

        // Analyze question-specific issues
        $incorrectAnswers = $answers->where('is_correct', false);
        if ($incorrectAnswers->count() > 0) {
            $areas[] = 'Attention to detail - review incorrect answers for patterns';
        }

        return $areas;
    }
}