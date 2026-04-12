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
                $correctAnswer = $questionData['correct_answers']
                    ?? $questionData['correct_answer']
                    ?? null;
                $questionType = $questionData['question_type'] ?? null;
                $pointsValue = $questionData['points']
                    ?? $questionData['points_value']
                    ?? 1;
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
            // Fallback inference (mainly for ReadingTask JSON questions)
            // If we have an object-like correct answer with option_key/option_text, treat as single choice.
            if (is_array($correctAnswer) && (isset($correctAnswer['option_key']) || isset($correctAnswer['option_text']) || isset($correctAnswer['id']))) {
                $questionType = 'choose_correct_answer';
            } elseif (is_array($correctAnswer) && array_keys($correctAnswer) === range(0, count($correctAnswer) - 1)) {
                // List-like correct answers: could be multi-choice or text completion; multi-choice compare handles strings/objects well.
                $questionType = 'choose_multiple_answer';
            } elseif (is_string($correctAnswer)) {
                $questionType = 'short_answer_question';
            } else {
                return;
            }
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

        $questionIdStr = (string) $questionId;

        foreach ($task->passages as $passage) {
            foreach ($passage['question_groups'] ?? [] as $group) {
                foreach ($group['questions'] ?? [] as $question) {
                    $qId = $question['id'] ?? null;
                    if ($qId !== null && (string) $qId === (string) $questionId) {
                        return $question;
                    }

                    // Some tasks store numeric question identifiers as question_number instead of id.
                    $qNumber = $question['question_number'] ?? null;
                    if ($qNumber !== null && (string) $qNumber === (string) $questionId) {
                        return $question;
                    }

                    // Support matching_* questions that store graded entities under items[]
                    $items = $question['items'] ?? null;
                    if (is_array($items)) {
                        $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');
                        foreach ($items as $item) {
                            $iId = $item['id'] ?? null;
                            if ($iId !== null && (string) $iId === (string) $questionId) {
                                return $item;
                            }
                            $iNumber = $item['question_number'] ?? null;
                            if ($iNumber !== null && (string) $iNumber === (string) $questionId) {
                                return $item;
                            }

                            // Support composite keys like "{parentKey}-item-{itemNumber}"
                            if ($parentKey !== '') {
                                $composite = $parentKey . '-item-' . (string) ($iNumber ?? '');
                                if ($iNumber !== null && $composite === $questionIdStr) {
                                    return $item;
                                }
                            }
                        }
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
            'sentence_completion', 'paragraph_completion', 'paragraph_summary_completion', 'note_completion', 'table_completion', 'flowchart_completion', 'diagram_label_completion' => $this->compareTextCompletion($studentAnswer, $correctAnswer),
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

    private function normalizeValue($value): string
    {
        return strtolower(trim((string) $value));
    }

    private function extractComparableValues($value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            $normalized = $this->normalizeValue($value);
            return $normalized === '' ? [] : [$normalized];
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);

        if ($isAssoc) {
            $candidates = [
                $value['id'] ?? null,
                $value['option_key'] ?? null,
                $value['option_text'] ?? null,
                $value['text'] ?? null,
                $value['value'] ?? null,
            ];

            return array_values(array_unique(array_filter(
                array_map(fn($item) => $item === null ? '' : $this->normalizeValue($item), $candidates),
                fn($item) => $item !== ''
            )));
        }

        $results = [];
        foreach ($value as $item) {
            foreach ($this->extractComparableValues($item) as $candidate) {
                $results[] = $candidate;
            }
        }

        return array_values(array_unique($results));
    }

    private function compareSingleChoice($student, $correct): bool
    {
        $studentValues = $this->extractComparableValues($student);
        $correctValues = $this->extractComparableValues($correct);

        return count(array_intersect($studentValues, $correctValues)) > 0;
    }

    private function compareMultipleChoice($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            return false;
        }

        // Prefer comparing option_key sets when correct answers are option objects.
        $correctPreferKeys = false;
        foreach ($correct as $c) {
            if (is_array($c) && array_key_exists('option_key', $c)) {
                $correctPreferKeys = true;
                break;
            }
        }

        $studentTokens = [];
        foreach ($student as $s) {
            if (is_array($s)) {
                $token = $s['option_key'] ?? $s['id'] ?? $s['option_text'] ?? $s['text'] ?? null;
                if ($token !== null && $token !== '') {
                    $studentTokens[] = $this->normalizeValue($token);
                }
            } else {
                $studentTokens[] = $this->normalizeValue($s);
            }
        }

        $correctTokens = [];
        foreach ($correct as $c) {
            if (is_array($c)) {
                $token = $correctPreferKeys
                    ? ($c['option_key'] ?? null)
                    : ($c['option_text'] ?? $c['text'] ?? $c['id'] ?? null);

                if ($token === null || $token === '') {
                    $token = $c['option_key'] ?? $c['option_text'] ?? $c['text'] ?? $c['id'] ?? null;
                }

                if ($token !== null && $token !== '') {
                    $correctTokens[] = $this->normalizeValue($token);
                }
            } else {
                $correctTokens[] = $this->normalizeValue($c);
            }
        }

        $studentTokens = array_values(array_unique(array_filter($studentTokens, fn($v) => $v !== '')));
        $correctTokens = array_values(array_unique(array_filter($correctTokens, fn($v) => $v !== '')));

        if (empty($studentTokens) || empty($correctTokens)) {
            return false;
        }

        sort($studentTokens);
        sort($correctTokens);

        return $studentTokens === $correctTokens;
    }

    private function compareTrueFalseNotGiven($student, $correct): bool
    {
        $studentStr = $this->normalizeValue($this->resolveValue($student));
        $validOptions = ['true', 'false', 'not given'];
        
        if (is_array($correct)) {
            foreach ($correct as $possible) {
                if (in_array($studentStr, $this->extractComparableValues($possible), true)) {
                    return true;
                }
            }
            return false;
        }

        $correctLower = $this->normalizeValue($correct);
        return in_array($studentStr, $validOptions) && $studentStr === $correctLower;
    }

    private function compareYesNoNotGiven($student, $correct): bool
    {
        $studentStr = $this->normalizeValue($this->resolveValue($student));
        $validOptions = ['yes', 'no', 'not given'];

        if (is_array($correct)) {
            foreach ($correct as $possible) {
                if (in_array($studentStr, $this->extractComparableValues($possible), true)) {
                    return true;
                }
            }
            return false;
        }

        $correctLower = $this->normalizeValue($correct);
        return in_array($studentStr, $validOptions) && $studentStr === $correctLower;
    }

    private function compareMatching($student, $correct): bool
    {
        if (!is_array($student) || !is_array($correct)) {
            // If it's a simple string match, allow it
            return count(array_intersect(
                $this->extractComparableValues($student),
                $this->extractComparableValues($correct)
            )) > 0;
        }

        // For matching questions, compare each pair
        foreach ($correct as $key => $value) {
            if (
                !isset($student[$key]) ||
                count(array_intersect(
                    $this->extractComparableValues($student[$key]),
                    $this->extractComparableValues($value)
                )) === 0
            ) {
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
                foreach ($this->extractComparableValues($possibleAnswer) as $candidate) {
                    if ($this->isTextMatch($studentStr, $candidate)) {
                        return true;
                    }
                }
            }

            if (array_keys($correct) !== range(0, count($correct) - 1)) {
                foreach ($this->extractComparableValues($correct) as $candidate) {
                    if ($this->isTextMatch($studentStr, $candidate)) {
                        return true;
                    }
                }
            }

            return false;
        }

        foreach ($this->extractComparableValues($correct) as $candidate) {
            if ($this->isTextMatch($studentStr, $candidate)) {
                    return true;
                }
        }

        return false;
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