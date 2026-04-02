<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningQuestionAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'question_id',
        'answer',
        'is_correct',
        'points_earned',
        'time_spent_seconds',
        'audio_play_count',
    ];

    protected $casts = [
        'answer' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ListeningSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestion::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    /**
     * Check if answer is correctly provided (for progress tracking)
     */
    public function isAnswered(): bool
    {
        return !empty($this->answer);
    }

    /**
     * Get formatted answer for display
     */
    public function getFormattedAnswerAttribute(): string
    {
        if (empty($this->answer)) {
            return 'No answer provided';
        }

        if (is_array($this->answer)) {
            return json_encode($this->answer);
        }

        return (string) $this->answer;
    }
}