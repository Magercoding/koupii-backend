<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Writing Feedback Model for scoring and detailed feedback
 * 
 * @property string $id
 * @property string $submission_id
 * @property string|null $question_id
 * @property string $feedback_type
 * @property float|null $score
 * @property float|null $max_score
 * @property string|null $comments
 * @property array|null $detailed_feedback
 * @property array|null $suggestions
 * @property string|null $graded_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_feedback';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'submission_id',
        'question_id',
        'feedback_type',
        'score',
        'max_score',
        'comments',
        'detailed_feedback',
        'suggestions',
        'graded_by',
    ];

    protected $casts = [
        'detailed_feedback' => 'array',
        'suggestions' => 'array',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    // Feedback types
    public const FEEDBACK_TYPES = [
        'overall' => 'Overall Feedback',
        'grammar' => 'Grammar Feedback',
        'content' => 'Content Feedback',
        'structure' => 'Structure Feedback',
        'vocabulary' => 'Vocabulary Feedback',
        'coherence' => 'Coherence & Cohesion',
    ];

    /**
     * Get the submission that owns this feedback
     */
    public function submission()
    {
        return $this->belongsTo(WritingSubmission::class, 'submission_id');
    }

    /**
     * Get the question this feedback is for
     */
    public function question()
    {
        return $this->belongsTo(WritingTaskQuestion::class, 'question_id');
    }

    /**
     * Get the grader (teacher/admin)
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Calculate percentage score
     */
    public function getPercentageScoreAttribute()
    {
        if (!$this->score || !$this->max_score || $this->max_score == 0) {
            return 0;
        }

        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Get score grade level
     */
    public function getGradeLevelAttribute()
    {
        $percentage = $this->percentage_score;

        if ($percentage >= 90) return 'A+';
        if ($percentage >= 85) return 'A';
        if ($percentage >= 80) return 'A-';
        if ($percentage >= 75) return 'B+';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 65) return 'B-';
        if ($percentage >= 60) return 'C+';
        if ($percentage >= 55) return 'C';
        if ($percentage >= 50) return 'C-';
        return 'F';
    }
}