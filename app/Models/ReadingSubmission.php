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
        'reading_task_id',
        'assignment_id',
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

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function studentAssignment(): BelongsTo
    {
        return $this->belongsTo(StudentAssignment::class, 'assignment_id', 'assignment_id')
            ->where('student_id', $this->student_id);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function readingTask(): BelongsTo
    {
        return $this->belongsTo(ReadingTask::class, 'reading_task_id');
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
        
        // Handle max points calculation based on test type (legacy Test vs new ReadingTask)
        $maxPossiblePoints = 0;
        if ($this->reading_task_id && $this->readingTask) {
            // New ReadingTask: calculate points from JSON passages
            $passages = $this->readingTask->passages ?? [];
            foreach ($passages as $passage) {
                foreach ($passage['question_groups'] ?? [] as $group) {
                    foreach ($group['questions'] ?? [] as $question) {
                        $maxPossiblePoints += (
                            $question['points']
                            ?? $question['points_value']
                            ?? 1
                        );
                    }
                }
            }
        } elseif ($this->test_id && $this->test) {
            // Legacy Test: calculate from related tables
            $maxPossiblePoints = $this->test->testQuestions()->sum('points_value');
        }
        
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
        if ($this->reading_task_id && $this->readingTask) {
            return $this->readingTask->allow_retake && 
                   ($this->readingTask->max_retake_attempts === null || 
                    $this->attempt_number < $this->readingTask->max_retake_attempts);
        }

        if ($this->test_id && $this->test) {
            return $this->test->allow_repetition && 
                   ($this->test->max_repetition_count === null || 
                    $this->attempt_number < $this->test->max_repetition_count);
        }

        return false;
    }
}