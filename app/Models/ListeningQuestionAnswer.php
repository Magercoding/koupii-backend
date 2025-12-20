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
        'selected_option_id',
        'text_answer',
        'answer_data',
        'is_correct',
        'points_earned',
        'time_spent_seconds',
        'play_count',
        'answer_explanation'
    ];

    protected $casts = [
        'answer_data' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ListeningSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    // Evaluate answer correctness
    public function evaluateAnswer(): void
    {
        $question = $this->question;
        $isCorrect = false;
        $pointsEarned = 0;

        switch ($question->question_type) {
            case 'multiple_choice':
            case 'single_correct':
                if ($this->selected_option_id) {
                    $correctOption = $question->options()->where('is_correct', true)->first();
                    $isCorrect = $correctOption && $correctOption->id === $this->selected_option_id;
                }
                break;

            case 'multiple_correct':
                if ($this->answer_data && isset($this->answer_data['selected_options'])) {
                    $correctOptionIds = $question->options()->where('is_correct', true)->pluck('id')->toArray();
                    $selectedOptionIds = $this->answer_data['selected_options'];
                    $isCorrect = count($correctOptionIds) === count($selectedOptionIds) && 
                                empty(array_diff($correctOptionIds, $selectedOptionIds));
                }
                break;

            case 'true_false':
                if ($this->selected_option_id) {
                    $correctOption = $question->options()->where('is_correct', true)->first();
                    $isCorrect = $correctOption && $correctOption->id === $this->selected_option_id;
                }
                break;

            case 'fill_in_the_blank':
            case 'short_answer':
                if ($this->text_answer) {
                    $correctAnswers = $question->options()->where('is_correct', true)->pluck('option_text')->toArray();
                    $studentAnswer = trim(strtolower($this->text_answer));
                    foreach ($correctAnswers as $correctAnswer) {
                        if (strtolower(trim($correctAnswer)) === $studentAnswer) {
                            $isCorrect = true;
                            break;
                        }
                    }
                }
                break;

            case 'matching':
                if ($this->answer_data && isset($this->answer_data['matches'])) {
                    $correctMatches = $question->questionBreakdowns()
                        ->with('questionGroup')
                        ->get()
                        ->pluck('questionGroup.correct_answer', 'id')
                        ->toArray();
                    
                    $studentMatches = $this->answer_data['matches'];
                    $isCorrect = count($correctMatches) === count($studentMatches);
                    
                    foreach ($correctMatches as $breakdownId => $correctAnswer) {
                        if (!isset($studentMatches[$breakdownId]) || 
                            $studentMatches[$breakdownId] !== $correctAnswer) {
                            $isCorrect = false;
                            break;
                        }
                    }
                }
                break;

            case 'drag_drop':
            case 'ordering':
                if ($this->answer_data && isset($this->answer_data['order'])) {
                    $correctOrder = $question->options()
                        ->orderBy('display_order')
                        ->pluck('id')
                        ->toArray();
                    $studentOrder = $this->answer_data['order'];
                    $isCorrect = $correctOrder === $studentOrder;
                }
                break;

            case 'highlight_text':
                if ($this->answer_data && isset($this->answer_data['highlighted_text'])) {
                    $correctHighlights = $question->options()
                        ->where('is_correct', true)
                        ->pluck('option_text')
                        ->toArray();
                    $studentHighlights = $this->answer_data['highlighted_text'];
                    
                    $isCorrect = count($correctHighlights) === count($studentHighlights) &&
                                empty(array_diff($correctHighlights, $studentHighlights));
                }
                break;

            case 'audio_response':
                // For audio responses, manual evaluation is typically required
                // This will be updated after teacher review
                $isCorrect = $this->is_correct ?? false;
                break;

            case 'listening_comprehension':
            case 'audio_dictation':
                // Similar to text-based evaluation but with audio context
                if ($this->text_answer) {
                    $correctAnswers = $question->options()->where('is_correct', true)->pluck('option_text')->toArray();
                    $studentAnswer = trim(strtolower($this->text_answer));
                    foreach ($correctAnswers as $correctAnswer) {
                        if (strtolower(trim($correctAnswer)) === $studentAnswer) {
                            $isCorrect = true;
                            break;
                        }
                    }
                }
                break;

            default:
                // For other question types, use manual evaluation
                $isCorrect = $this->is_correct ?? false;
                break;
        }

        if ($isCorrect) {
            $pointsEarned = $question->points_value ?? 1;
        }

        $this->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned
        ]);
    }

    // Record audio play for this question
    public function recordPlay(): void
    {
        $this->increment('play_count');
    }

    // Check if answer is submitted
    public function isAnswered(): bool
    {
        return !is_null($this->selected_option_id) || 
               !is_null($this->text_answer) || 
               !empty($this->answer_data);
    }

    // Get formatted answer for display
    public function getFormattedAnswerAttribute(): string
    {
        if ($this->text_answer) {
            return $this->text_answer;
        }

        if ($this->selectedOption) {
            return $this->selectedOption->option_text;
        }

        if ($this->answer_data) {
            return json_encode($this->answer_data);
        }

        return 'No answer provided';
    }
}