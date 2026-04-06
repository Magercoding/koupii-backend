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
        if (is_null($answer)) {
            return false;
        }

        // If it's a comma-separated string from the frontend, split it first
        // But NOT for sentence_completion-like types where commas can be part of a single gap's answer
        $noSplitTypes = ['sentence_completion', 'short_answer', 'note_completion', 'form_completion', 'gap_fill', 'summary_completion', 'table_completion', 'flowchart_completion'];
        if (is_string($answer) && str_contains($answer, ',') && !in_array($this->question_type, $noSplitTypes)) {
            $answer = array_map('trim', explode(',', $answer));
        }

        if (!is_array($answer)) {
            $answer = [$answer];
        }

        // Standardize student answers: trim, lowercase, and resolve labels to keys if possible
        $studentValues = is_array($answer) ? $answer : [$answer];
        $normalizedStudentAnswers = array_values(array_unique(array_filter(array_map(function ($val) {
            // 1. Handle object-based answers (prioritize key/value over text)
            if (is_array($val)) {
                $val = $val['option_key'] ?? $val['value'] ?? $val['text'] ?? json_encode($val);
            }
            
            $rawVal = trim((string)$val);
            $normalizedVal = strtolower($rawVal);

            // 2. REVERSE LOOKUP: If normalizedVal doesn't look like a typical short key (like A, B, C),
            // or even if it does, try to see if it matches an option TEXT and get its KEY instead.
            // This handles cases where frontend sends labels but backend wants keys.
            if ($this->options && is_array($this->options)) {
                foreach ($this->options as $opt) {
                    $optKey = strtolower(trim((string)($opt['key'] ?? $opt['option_key'] ?? '')));
                    $optText = strtolower(trim((string)($opt['text'] ?? $opt['label'] ?? '')));
                    
                    if ($normalizedVal === $optText && $optKey !== '') {
                        return $optKey; // Translate label to key
                    }
                }
            }

            return preg_replace('/\s+/', ' ', $normalizedVal);
        }, $studentValues))));

        // Handle case where correct_answers might not be an array despite casting
        $correctAnswers = $this->correct_answers;
        if (is_string($correctAnswers)) {
            $decoded = json_decode($correctAnswers, true);
            $correctAnswers = is_array($decoded) ? $decoded : [$correctAnswers];
        }

        if (empty($correctAnswers)) {
            return false;
        }

        // Normalize correct answers
        $normalizedCorrectAnswers = array_map(function ($val) {
            if (is_array($val)) {
                $val = $val['option_key'] ?? $val['value'] ?? $val['text'] ?? json_encode($val);
            }
            $s = strtolower(trim((string)$val));
            return preg_replace('/\s+/', ' ', $s);
        }, $correctAnswers);

        // For multiple answer questions: Require exact match of sets
        if ($this->question_type === 'multiple_answer') {
            if (empty($normalizedStudentAnswers)) return false;
            
            $intersect = array_intersect($normalizedStudentAnswers, $normalizedCorrectAnswers);
            
            // Must match count and contain all correct answers
            return count($intersect) === count($normalizedCorrectAnswers) && 
                   count($normalizedStudentAnswers) === count($normalizedCorrectAnswers);
        }

        // For single answer questions
        $studentFirstAnswer = $normalizedStudentAnswers[0] ?? null;
        if (is_null($studentFirstAnswer)) {
            return false;
        }

        // 1. Check if it matches any of the alternatives directly
        if (in_array($studentFirstAnswer, $normalizedCorrectAnswers)) {
            return true;
        }

        // 2. For completion questions, check if student input matches the phrase joined together
        // This handles cases where multi-word answers are stored as separate array elements in DB
        $completionTypes = ['sentence_completion', 'short_answer', 'note_completion', 'form_completion', 'gap_fill', 'summary_completion', 'table_completion', 'flowchart_completion'];
        if (in_array($this->question_type, $completionTypes) && count($normalizedCorrectAnswers) > 1) {
            $joinedPhrase = implode(' ', $normalizedCorrectAnswers);
            $joinedPhraseComma = implode(', ', $normalizedCorrectAnswers);
            
            if ($studentFirstAnswer === $joinedPhrase || $studentFirstAnswer === $joinedPhraseComma) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate points earned for given answer
     */
    public function calculatePoints($answer): int
    {
        return $this->isCorrectAnswer($answer) ? $this->points : 0;
    }
}