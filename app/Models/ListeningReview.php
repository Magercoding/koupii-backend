<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $submission_id
 * @property string|null $teacher_id
 * @property int|null $score
 * @property string|null $comments
 * @property array|null $feedback_json
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ListeningReview extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'listening_reviews';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'submission_id',
        'teacher_id',
        'score',
        'comments',
        'feedback_json',
        'reviewed_at',
    ];

    protected $casts = [
        'feedback_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the submission this review belongs to
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ListeningSubmission::class, 'submission_id');
    }

    /**
     * Get the teacher who reviewed this submission
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Mark submission as reviewed
     */
    public function markAsReviewed(): void
    {
        $this->reviewed_at = now();
        $this->save();
        
        // Update submission status
        $this->submission->update(['status' => 'reviewed']);
    }

    /**
     * Get feedback for specific question type
     */
    public function getFeedbackForQuestionType(string $questionType): ?array
    {
        return $this->feedback_json[$questionType] ?? null;
    }

    /**
     * Add feedback for specific question type
     */
    public function addQuestionTypeFeedback(string $questionType, array $feedback): void
    {
        $feedbackJson = $this->feedback_json ?? [];
        $feedbackJson[$questionType] = $feedback;
        $this->feedback_json = $feedbackJson;
        $this->save();
    }
}