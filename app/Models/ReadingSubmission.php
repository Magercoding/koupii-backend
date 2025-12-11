<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'test_id',
        'student_id',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'time_taken_seconds',
        'total_score',
        'percentage',
        'total_correct',
        'total_incorrect',
        'total_unanswered'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ReadingQuestionAnswer::class, 'submission_id');
    }

    public function vocabularyDiscoveries(): HasMany
    {
        return $this->hasMany(StudentVocabularyDiscovery::class, 'test_id', 'test_id')
                    ->where('student_id', $this->student_id);
    }

    // Calculate final score
    public function calculateScore(): void
    {
        $totalQuestions = $this->answers()->count();
        $correctAnswers = $this->answers()->where('is_correct', true)->count();
        $incorrectAnswers = $this->answers()->where('is_correct', false)->count();
        $unanswered = $totalQuestions - $correctAnswers - $incorrectAnswers;

        $totalPoints = $this->answers()->sum('points_earned');
        $maxPossiblePoints = $this->test->testQuestions()->sum('points_value');
        
        $percentage = $maxPossiblePoints > 0 ? ($totalPoints / $maxPossiblePoints) * 100 : 0;

        $this->update([
            'total_score' => $totalPoints,
            'percentage' => $percentage,
            'total_correct' => $correctAnswers,
            'total_incorrect' => $incorrectAnswers,
            'total_unanswered' => $unanswered,
        ]);
    }

    // Check if submission is completed
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Get grade based on percentage
    public function getGradeAttribute(): string
    {
        if (is_null($this->percentage)) {
            return 'N/A';
        }

        return match (true) {
            $this->percentage >= 90 => 'A',
            $this->percentage >= 80 => 'B', 
            $this->percentage >= 70 => 'C',
            $this->percentage >= 60 => 'D',
            default => 'F'
        };
    }

    // Check if student can retake
    public function canRetake(): bool
    {
        return $this->test->allow_repetition && 
               ($this->test->max_repetition_count === null || 
                $this->attempt_number < $this->test->max_repetition_count);
    }
}