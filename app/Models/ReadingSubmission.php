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
        // For ReadingTask (JSON), we may have legacy/orphan answer rows from older schemas.
        // Only count answers that match current task questions/items.
        $validReadingTaskIds = null;
        if ($this->reading_task_id && $this->readingTask) {
            $validReadingTaskIds = [];
            $passages = $this->readingTask->passages ?? [];
            foreach ($passages as $passage) {
                foreach ($passage['question_groups'] ?? [] as $group) {
                    foreach ($group['questions'] ?? [] as $question) {
                        $items = $question['items'] ?? null;
                        $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');

                        if (is_array($items) && count($items) > 0) {
                            foreach ($items as $idx => $item) {
                                $itemNum = $item['question_number'] ?? ($idx + 1);
                                $itemKey = $item['id']
                                    ?? ($parentKey !== '' ? ($parentKey . '-item-' . (string) $itemNum) : (string) $itemNum);
                                if ($itemKey !== null && (string) $itemKey !== '') {
                                    $validReadingTaskIds[] = (string) $itemKey;
                                }
                            }
                        } else {
                            $qKey = $question['id'] ?? $question['question_number'] ?? null;
                            if ($qKey !== null && (string) $qKey !== '') {
                                $validReadingTaskIds[] = (string) $qKey;
                            }
                        }
                    }
                }
            }

            $validReadingTaskIds = array_values(array_unique($validReadingTaskIds));
        }

        $answersQuery = $this->answers();
        if (is_array($validReadingTaskIds)) {
            $answersQuery = $answersQuery->whereIn('reading_task_question_id', $validReadingTaskIds);
        }

        $totalQuestions = $answersQuery->count();
        $correctAnswers = (clone $answersQuery)->where('is_correct', true)->count();
        $incorrectAnswers = (clone $answersQuery)->where('is_correct', false)->count();
        $unanswered = $totalQuestions - $correctAnswers - $incorrectAnswers;

        $totalPoints = (clone $answersQuery)->sum('points_earned');
        
        // Handle max points calculation based on test type (legacy Test vs new ReadingTask)
        $maxPossiblePoints = 0;
        if ($this->reading_task_id && $this->readingTask) {
            // New ReadingTask: calculate points from JSON passages
            $passages = $this->readingTask->passages ?? [];
            foreach ($passages as $passage) {
                foreach ($passage['question_groups'] ?? [] as $group) {
                    foreach ($group['questions'] ?? [] as $question) {
                        $items = $question['items'] ?? null;
                        if (is_array($items) && count($items) > 0) {
                            foreach ($items as $item) {
                                $maxPossiblePoints += (
                                    $item['points']
                                    ?? $item['points_value']
                                    ?? 1
                                );
                            }
                        } else {
                            $maxPossiblePoints += (
                                $question['points']
                                ?? $question['points_value']
                                ?? 1
                            );
                        }
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