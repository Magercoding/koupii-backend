<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $listening_task_id
 * @property string $student_id
 * @property array|null $files
 * @property string $status
 * @property int $attempt_number
 * @property int|null $time_taken_seconds
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $started_at
 * @property float|null $total_score
 * @property float|null $percentage
 * @property int $total_correct
 * @property int $total_incorrect
 * @property int $total_unanswered
 * @property array|null $audio_play_counts
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ListeningSubmission extends Model
{
    use HasFactory, HasUuids;

    // Status options following writing pattern
    public const STATUS_TO_DO = 'to_do';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_TO_DO => 'To Do',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_REVIEWED => 'Reviewed',
        self::STATUS_DONE => 'Done'
    ];

    protected $fillable = [
        'listening_task_id',
        'student_id',
        'files',
        'status',
        'attempt_number',
        'time_taken_seconds',
        'submitted_at',
        'started_at',
        'total_score',
        'percentage',
        'total_correct',
        'total_incorrect',
        'total_unanswered',
        'audio_play_counts'
    ];

    protected $casts = [
        'files' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'audio_play_counts' => 'array'
    ];

    /**
     * Get the listening task for this submission
     */
    public function listeningTask(): BelongsTo
    {
        return $this->belongsTo(ListeningTask::class, 'listening_task_id');
    }

    /**
     * Get the student who made this submission
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get all answers for this submission
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ListeningQuestionAnswer::class, 'submission_id');
    }

    /**
     * Get the review for this submission
     */
    public function review(): HasOne
    {
        return $this->hasOne(ListeningReview::class, 'submission_id');
    }

    /**
     * Scope for submissions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for completed submissions
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Check if submission is submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_REVIEWED, self::STATUS_DONE]);
    }

    /**
     * Check if submission is reviewed
     */
    public function isReviewed(): bool
    {
        return in_array($this->status, [self::STATUS_REVIEWED, self::STATUS_DONE]);
    }

    /**
     * Submit the submission
     */
    public function submit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now()
        ]);
    }

    /**
     * Calculate and update scores
     */
    public function calculateScores(): void
    {
        $answers = $this->answers()->with('question')->get();
        
        $totalCorrect = $answers->where('is_correct', true)->count();
        $totalIncorrect = $answers->where('is_correct', false)->count();
        $totalUnanswered = $this->listeningTask->questions()->count() - $answers->count();
        
        $totalPoints = $this->listeningTask->getTotalPoints();
        $earnedPoints = $answers->sum('points_earned');
        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        $this->update([
            'total_correct' => $totalCorrect,
            'total_incorrect' => $totalIncorrect,
            'total_unanswered' => $totalUnanswered,
            'total_score' => $earnedPoints,
            'percentage' => $percentage
        ]);
    }

    /**
     * Get retake options if available
     */
    public function getRetakeOptions(): ?array
    {
        if (!$this->listeningTask->allowsRetakes()) {
            return null;
        }

        if ($this->attempt_number >= $this->listeningTask->max_retake_attempts) {
            return null;
        }

        return $this->listeningTask->retake_options;
    }
}