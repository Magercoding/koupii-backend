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
                        $qType = $question['question_type'] ?? '';

                        // Note-completion: blanks are stored as separate answer records
                        if ($qType === 'note_completion') {
                            $blankCorrectAnswers = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                            if (is_array($blankCorrectAnswers) && count($blankCorrectAnswers) > 0) {
                                foreach ($blankCorrectAnswers as $blank) {
                                    $blankKey = $blank['option_key'] ?? null;
                                    if ($blankKey !== null) {
                                        $answerId = $parentKey !== '' ? "{$parentKey}-blank-{$blankKey}" : "blank-{$blankKey}";
                                        $validReadingTaskIds[] = $answerId;
                                    }
                                }
                                continue;
                            }
                        }

                        // Table completion: same composite id pattern as the student UI (`{id}-blank-{row}-{col}`)
                        if ($qType === 'table_completion') {
                            $tableBlanks = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                            if (is_array($tableBlanks) && count($tableBlanks) > 0 && $parentKey !== '') {
                                foreach ($tableBlanks as $blank) {
                                    $cellKey = $blank['option_key'] ?? null;
                                    if ($cellKey !== null && (string) $cellKey !== '') {
                                        $validReadingTaskIds[] = "{$parentKey}-blank-{$cellKey}";
                                    }
                                }
                                continue;
                            }
                        }

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
        $correctCount = (clone $answersQuery)->where('is_correct', true)->count();
        $incorrectCount = (clone $answersQuery)->where('is_correct', false)->count();
        $unanswered = $totalQuestions - $correctCount - $incorrectCount;

        $totalPoints = (clone $answersQuery)->sum('points_earned');
        
        // Handle max points calculation based on test type (legacy Test vs new ReadingTask)
        $maxPossiblePoints = 0;
        if ($this->reading_task_id && $this->readingTask) {
            // New ReadingTask: calculate points from JSON passages
            $passages = $this->readingTask->passages ?? [];
            foreach ($passages as $passage) {
                foreach ($passage['question_groups'] ?? [] as $group) {
                    foreach ($group['questions'] ?? [] as $question) {
                        $qType = $question['question_type'] ?? '';
                        $items = $question['items'] ?? null;

                        // Note-completion: sum per-blank points_value
                        if ($qType === 'note_completion') {
                            $blankAnswers = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                            if (is_array($blankAnswers) && count($blankAnswers) > 0) {
                                $questionTotal = (float) ($question['points_value'] ?? $question['points'] ?? 1);
                                $blankCount = count($blankAnswers);
                                foreach ($blankAnswers as $blank) {
                                    $maxPossiblePoints += (float) (
                                        $blank['points_value']
                                        ?? floor($questionTotal / $blankCount)
                                    );
                                }
                                continue;
                            }
                        }

                        // Table completion: per-blank max points (matches note_completion)
                        if ($qType === 'table_completion') {
                            $blankAnswers = $question['correct_answers'] ?? $question['correct_answer'] ?? [];
                            if (is_array($blankAnswers) && count($blankAnswers) > 0) {
                                $questionTotal = (float) ($question['points_value'] ?? $question['points'] ?? 1);
                                $blankCount = count($blankAnswers);
                                foreach ($blankAnswers as $blank) {
                                    $maxPossiblePoints += (float) (
                                        $blank['points_value']
                                        ?? floor($questionTotal / $blankCount)
                                    );
                                }
                                continue;
                            }
                        }

                        if (is_array($items) && count($items) > 0) {
                            // Use item-level points_value when available (each item has its own),
                            // otherwise fall back to parent points_value per item.
                            $parentPoints = (float) ($question['points'] ?? $question['points_value'] ?? 1);
                            foreach ($items as $item) {
                                $maxPossiblePoints += (float) (
                                    $item['points']
                                    ?? $item['points_value']
                                    ?? $parentPoints
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
            // Legacy Test: calculate from related tables (Passage -> QuestionGroup -> TestQuestion)
            $maxPossiblePoints = 0;
            $this->test->loadMissing('passages.questionGroups.questions');
            foreach ($this->test->passages as $passage) {
                foreach ($passage->questionGroups as $group) {
                    $maxPossiblePoints += $group->questions->sum('points_value');
                }
            }
        }
        
        $percentage = $maxPossiblePoints > 0 ? ($totalPoints / $maxPossiblePoints) * 100 : 0;

        $this->update([
            'total_score' => $totalPoints,
            'percentage' => $percentage,
            'total_correct' => $correctCount,
            'total_incorrect' => $incorrectCount,
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