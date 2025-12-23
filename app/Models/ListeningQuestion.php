<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $listening_task_id
 * @property string $question_type
 * @property string $question_text
 * @property array|null $options
 * @property array $correct_answers
 * @property int $points
 * @property int $order_index
 * @property float|null $start_time
 * @property float|null $end_time
 * @property string|null $explanation
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ListeningQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'listening_questions';
    public $incrementing = false;
    protected $keyType = 'string';

    // 15 Question Types for Listening
    public const QUESTION_TYPES = [
        'multiple_choice' => 'Multiple Choice (QT1)',
        'multiple_answer' => 'Multiple Answer (QT2)', 
        'matching' => 'Matching/Map/Plan Labeling (QT3)',
        'table_completion' => 'Table Completion (QT4)',
        'sentence_completion' => 'Sentence Completion (QT5)',
        'short_answer' => 'Short Answer Question (QT6)',
        'form_completion' => 'Form Completion (QT7)',
        'note_completion' => 'Note Completion (QT8)',
        'flowchart_completion' => 'Flowchart Completion (QT9)',
        'summary_completion' => 'Summary Completion (QT10)',
        'diagram_labeling' => 'Diagram Labeling (QT11)',
        'classification' => 'Classification (QT12)',
        'true_false_not_given' => 'True/False/Not Given (QT13)',
        'yes_no_not_given' => 'Yes/No/Not Given (QT14)',
        'gap_fill' => 'Gap Fill (QT15)'
    ];

    protected $fillable = [
        'listening_task_id',
        'question_type',
        'question_text',
        'options',
        'correct_answers',
        'points',
        'order_index',
        'start_time',
        'end_time',
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answers' => 'array',
        'start_time' => 'decimal:2',
        'end_time' => 'decimal:2',
    ];

    /**
     * Get the listening task this question belongs to
     */
    public function listeningTask(): BelongsTo
    {
        return $this->belongsTo(ListeningTask::class, 'listening_task_id');
    }

    /**
     * Get all answers for this question
     */
    public function questionAnswers(): HasMany
    {
        return $this->hasMany(ListeningQuestionAnswer::class, 'question_id');
    }

    /**
     * Scope to order by index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Check if answer is correct
     */
    public function isCorrectAnswer($answer): bool
    {
        if (!is_array($answer)) {
            $answer = [$answer];
        }

        // For multiple answer questions, check if all correct answers are provided
        if ($this->question_type === 'multiple_answer') {
            return count(array_intersect($answer, $this->correct_answers)) === count($this->correct_answers);
        }

        // For single answer questions
        return in_array($answer[0] ?? null, $this->correct_answers);
    }

    /**
     * Calculate points earned for given answer
     */
    public function calculatePoints($answer): int
    {
        return $this->isCorrectAnswer($answer) ? $this->points : 0;
    }
}