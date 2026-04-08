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
        'reading_task_question_id',
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
        $correctAnswer = null;
        $questionType = null;
        $pointsValue = 1;

        if ($this->submission->reading_task_id) {
            // Support for new ReadingTask JSON questions
            $task = $this->submission->readingTask;
            $questionData = $this->findQuestionInTask($task, $this->reading_task_question_id);
            
            if ($questionData) {
                $correctAnswer = $questionData['correct_answers'] ?? null;
                $questionType = $questionData['question_type'] ?? null;
                $pointsValue = $questionData['points'] ?? 1;
            }
        } else {
            // Support for legacy Test model
            $question = $this->question;
            if ($question) {
                $correctAnswer = $question->correct_answers;
                $questionType = $question->question_type;
                $pointsValue = $question->points_value;
            }
        }

        // If we still don't have a correct answer but we have it saved, use it as fallback
        if ($correctAnswer === null && $this->correct_answer !== null) {
            $correctAnswer = $this->correct_answer;
        }

        if (!$questionType) {
            // Try to guess question type from data if possible, but if not we can't score
            return;
        }

        $isCorrect = $this->compareAnswers($studentAnswer, $correctAnswer, $questionType);
        $pointsEarned = $isCorrect ? $pointsValue : 0;

        $this->update([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'correct_answer' => $correctAnswer // Update with the fresh one from source
        ]);
    }

    private function findQuestionInTask($task, $questionId): ?array
    {
        if (!$task || !$task->passages) return null;

        foreach ($task->passages as $passage) {
            foreach ($passage['question_groups'] ?? [] as $group) {
                foreach ($group['questions'] ?? [] as $question) {
                    if (($question['id'] ?? '') === $questionId) {
                        return $question;
                    }
                }
            }
        }

        return null;
    }

    // Compare answers based on question type
    private function compareAnswers($studentAnswer, $correctAnswer, $questionType): bool
    {
        if (empty($studentAnswer) || empty($correctAnswer)) {
            return false;
        }

        return match ($questionType) {
            'choose_correct_answer', 'multiple_choice' => $this->compareSingleChoice($studentAnswer, $correctAnswer),
            'choose_multiple_answer' => $this->compareMultipleChoice($studentAnswer, $correctAnswer),
            'true_false_not_given' => $this->compareTrueFalseNotGiven($studentAnswer, $correctAnswer),
            'yes_no_not_given' => $this->compareYesNoNotGiven($studentAnswer, $correctAnswer),
            'matching_heading', 'matching_information', 'matching_features', 'matching_sentence_ending' => $this->compareMatching($studentAnswer, $correctAnswer),
            'sentence_completion', 'paragraph_summary_completion', 'note_completion', 'table_completion', 'flowchart_completion', 'diagram_label_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
            'short_answer_question' => $this->compareShortAnswer($studentAnswer, $correctAnswer),
            default => false
        };
    }

    private function resolveValue($val): string
    {
        if (is_array($val)) {
            return count($val) > 0 ? (string) reset($val) : '';
        }
        return (string) ($val ?? '');
    }

    private function compareSingleChoice($student, $correct): bool
    {
        $studentStr = strtolower(trim($this->resolveValue($student)));
        
        if (is_array($correct)) {
            foreach ($correct as $possible) {
                if ($studentStr === strtolower(trim($this->resolveValue($possible)))) {
                    return true;
                }
            }
            return false;
        }
        
        return $studentStr === strtolower(trim((string)$correct));
    }

    private function compareMultipleChoice($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            // Even if one is not array, try comparing as single choice if they happen to be strings
            if (!is_array($student) && !is_array($correct)) {
                return strtolower(trim($student)) === strtolower(trim($correct));
            }
            return false;
        }

        $studentClean = array_map(fn($item) => strtolower(trim((string)$item)), $student);
        $correctClean = array_map(fn($item) => strtolower(trim((string)$item)), $correct);

        sort($studentClean);
        sort($correctClean);

        return $studentClean === $correctClean;
    }

    private function compareTrueFalseNotGiven($student, $correct): bool
    {
        $studentStr = strtolower(trim($this->resolveValue($student)));
        $validOptions = ['true', 'false', 'not given'];
        
        if (is_array($correct)) {
            foreach ($correct as $possible) {
                if ($studentStr === strtolower(trim($this->resolveValue($possible)))) {
                    return true;
                }
            }
            return false;
        }

        $correctLower = strtolower(trim((string)$correct));
        return in_array($studentStr, $validOptions) && $studentStr === $correctLower;
    }

    private function compareYesNoNotGiven($student, $correct): bool
    {
        $studentStr = strtolower(trim($this->resolveValue($student)));
        $validOptions = ['yes', 'no', 'not given'];

        if (is_array($correct)) {
            foreach ($correct as $possible) {
                if ($studentStr === strtolower(trim($this->resolveValue($possible)))) {
                    return true;
                }
            }
            return false;
        }

        $correctLower = strtolower(trim((string)$correct));
        return in_array($studentStr, $validOptions) && $studentStr === $correctLower;
    }

    private function compareMatching($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            // If it's a simple string match, allow it
            return strtolower(trim($this->resolveValue($student))) === strtolower(trim($this->resolveValue($correct)));
        }

        // For matching questions, compare each pair
        foreach ($correct as $key => $value) {
            if (!isset($student[$key]) || (string)$student[$key] !== (string)$value) {
                return false;
            }
        }

        return true;
    }

    private function compareTextCompletion($student, $correct): bool
    {
        $studentStr = $this->resolveValue($student);
        
        if (is_array($correct)) {
            foreach ($correct as $possibleAnswer) {
                if ($this->isTextMatch($studentStr, $this->resolveValue($possibleAnswer))) {
                    return true;
                }
            }
            return false;
        }

        return $this->isTextMatch($studentStr, (string)$correct);
    }

    private function compareShortAnswer($student, $correct): bool
    {
        return $this->compareTextCompletion($student, $correct);
    }

    private function isTextMatch($student, $correct): bool
    {
        $student = strtolower(preg_replace('/[^\w\s]/', '', (string)$student));
        $correct = strtolower(preg_replace('/[^\w\s]/', '', (string)$correct));

        return trim($student) === trim($correct);
    }
}