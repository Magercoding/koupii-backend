<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakingReview extends Model
{
    use HasFactory, HasUuids;


    protected $table = 'speaking_reviews';
    protected $fillable = [
        'submission_id',
        'teacher_id',
        'total_score',
        'overall_feedback',
        'question_scores',
        'reviewed_at'
    ];

    protected $casts = [
        'question_scores' => 'array',
        'reviewed_at' => 'datetime'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SpeakingSubmission::class, 'submission_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Get score for a specific question
    public function getQuestionScore(string $questionId): ?array
    {
        if (!$this->question_scores || !is_array($this->question_scores)) {
            return null;
        }

        return collect($this->question_scores)->firstWhere('question_id', $questionId);
    }

    // Set score for a specific question
    public function setQuestionScore(string $questionId, int $score, ?string $comment = null): void
    {
        $questionScores = $this->question_scores ?? [];

        // Remove existing score for this question
        $questionScores = array_filter($questionScores, function ($item) use ($questionId) {
            return $item['question_id'] !== $questionId;
        });

        // Add new score
        $questionScores[] = [
            'question_id' => $questionId,
            'score' => $score,
            'comment' => $comment
        ];

        $this->question_scores = array_values($questionScores);
    }

    // Calculate average question score
    public function getAverageQuestionScoreAttribute(): ?float
    {
        if (!$this->question_scores || empty($this->question_scores)) {
            return null;
        }

        $scores = array_column($this->question_scores, 'score');
        $validScores = array_filter($scores, function ($score) {
            return is_numeric($score);
        });

        if (empty($validScores)) {
            return null;
        }

        return round(array_sum($validScores) / count($validScores), 2);
    }

    // Get formatted score display
    public function getScoreDisplayAttribute(): string
    {
        if (is_null($this->total_score)) {
            return 'Not scored';
        }

        return $this->total_score . '/100';
    }

    // Get grade letter based on score
    public function getGradeAttribute(): string
    {
        if (is_null($this->total_score)) {
            return 'N/A';
        }

        return match (true) {
            $this->total_score >= 90 => 'A',
            $this->total_score >= 80 => 'B',
            $this->total_score >= 70 => 'C',
            $this->total_score >= 60 => 'D',
            default => 'F'
        };
    }

    // Check if review is complete
    public function isComplete(): bool
    {
        return !is_null($this->total_score) && !is_null($this->reviewed_at);
    }

    // Get review summary
    public function getSummaryAttribute(): array
    {
        return [
            'total_score' => $this->total_score,
            'grade' => $this->grade,
            'score_display' => $this->score_display,
            'average_question_score' => $this->average_question_score,
            'questions_reviewed' => $this->question_scores ? count($this->question_scores) : 0,
            'has_feedback' => !empty($this->overall_feedback),
            'is_complete' => $this->isComplete(),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'reviewer' => $this->teacher?->name
        ];
    }

    // Scope for completed reviews
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('total_score')
            ->whereNotNull('reviewed_at');
    }

    // Scope for reviews by teacher
    public function scopeByTeacher($query, string $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    // Model events
    protected static function booted(): void
    {
        // Set reviewed_at when creating/updating
        static::saving(function (SpeakingReview $review) {
            if ($review->total_score && !$review->reviewed_at) {
                $review->reviewed_at = now();
            }
        });

        // Update submission status when review is completed
        static::saved(function (SpeakingReview $review) {
            if ($review->isComplete()) {
                $review->submission()->update(['status' => 'reviewed']);
            }
        });
    }
}