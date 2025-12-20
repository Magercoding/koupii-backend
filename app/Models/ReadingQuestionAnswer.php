<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingQuestionAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'question_id',
        'student_answer',
        'correct_answer',
        'is_correct',
        'points_earned',
        'time_spent_seconds'
    ];

    protected $casts = [
        'student_answer' => 'array',
        'correct_answer' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ReadingSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }

    // Check and calculate if answer is correct
    public function checkAnswer(): void
    {
        $studentAnswer = $this->student_answer;
        $correctAnswer = $this->correct_answer ?? $this->question->correct_answers;

        $isCorrect = $this->compareAnswers($studentAnswer, $correctAnswer, $this->question->question_type);
        
        $pointsEarned = $isCorrect ? $this->question->points_value : 0;

        $this->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'correct_answer' => $correctAnswer
        ]);
    }

    // Compare answers based on question type
    private function compareAnswers($studentAnswer, $correctAnswer, $questionType): bool
    {
        if (empty($studentAnswer) || empty($correctAnswer)) {
            return false;
        }

        return match ($questionType) {
            'choose_correct_answer' => $this->compareSingleChoice($studentAnswer, $correctAnswer),
            'choose_multiple_answer' => $this->compareMultipleChoice($studentAnswer, $correctAnswer),
            'true_false_not_given' => $this->compareTrueFalseNotGiven($studentAnswer, $correctAnswer),
            'yes_no_not_given' => $this->compareYesNoNotGiven($studentAnswer, $correctAnswer),
            'matching_heading' => $this->compareMatching($studentAnswer, $correctAnswer),
            'matching_information' => $this->compareMatching($studentAnswer, $correctAnswer),
            'matching_features' => $this->compareMatching($studentAnswer, $correctAnswer),
            'matching_sentence_ending' => $this->compareMatching($studentAnswer, $correctAnswer),
            'sentence_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'paragraph_summary_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'note_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'table_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'flowchart_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'diagram_label_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'short_answer_question' => $this->compareShortAnswer($studentAnswer, $correctAnswer),
            default => false
        };
    }

    private function compareSingleChoice($student, $correct): bool
    {
        return strtolower(trim($student)) === strtolower(trim($correct));
    }

    private function compareMultipleChoice($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            return false;
        }

        sort($student);
        sort($correct);

        return $student === $correct;
    }

    private function compareTrueFalseNotGiven($student, $correct): bool
    {
        $validOptions = ['true', 'false', 'not given'];
        $studentLower = strtolower(trim($student));
        $correctLower = strtolower(trim($correct));

        return in_array($studentLower, $validOptions) && $studentLower === $correctLower;
    }

    private function compareYesNoNotGiven($student, $correct): bool
    {
        $validOptions = ['yes', 'no', 'not given'];
        $studentLower = strtolower(trim($student));
        $correctLower = strtolower(trim($correct));

        return in_array($studentLower, $validOptions) && $studentLower === $correctLower;
    }

    private function compareMatching($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            return false;
        }

        // For matching questions, compare each pair
        foreach ($correct as $key => $value) {
            if (!isset($student[$key]) || $student[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    private function compareTextCompletion($student, $correct): bool
    {
        if (is_array($correct)) {
            // Multiple possible answers
            foreach ($correct as $possibleAnswer) {
                if ($this->isTextMatch(trim($student), trim($possibleAnswer))) {
                    return true;
                }
            }
            return false;
        }

        return $this->isTextMatch(trim($student), trim($correct));
    }

    private function compareShortAnswer($student, $correct): bool
    {
        return $this->compareTextCompletion($student, $correct);
    }

    private function isTextMatch($student, $correct): bool
    {
        // Case-insensitive comparison, allowing for minor variations
        $student = strtolower(preg_replace('/[^\w\s]/', '', $student));
        $correct = strtolower(preg_replace('/[^\w\s]/', '', $correct));

        return $student === $correct;
    }
}