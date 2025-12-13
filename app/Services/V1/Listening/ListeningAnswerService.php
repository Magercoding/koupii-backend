<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningQuestionAnswer;
use App\Models\ListeningSubmission;
use App\Models\TestQuestion;
use Illuminate\Support\Facades\DB;

class ListeningAnswerService
{
    /**
     * Save answer for a listening question
     */
    public function saveAnswer(ListeningSubmission $submission, array $data): ListeningQuestionAnswer
    {
        return DB::transaction(function () use ($submission, $data) {
            $answer = ListeningQuestionAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'question_id' => $data['question_id']
                ],
                [
                    'selected_option_id' => $data['selected_option_id'] ?? null,
                    'text_answer' => $data['text_answer'] ?? null,
                    'answer_data' => $data['answer_data'] ?? null,
                    'time_spent_seconds' => $data['time_spent_seconds'] ?? 0,
                    'play_count' => $data['play_count'] ?? 0
                ]
            );

            // Auto-evaluate the answer if possible
            $this->evaluateAnswer($answer);

            return $answer->load(['question', 'selectedOption']);
        });
    }

    /**
     * Update existing answer
     */
    public function updateAnswer(ListeningQuestionAnswer $answer, array $data): ListeningQuestionAnswer
    {
        return DB::transaction(function () use ($answer, $data) {
            $answer->update([
                'selected_option_id' => $data['selected_option_id'] ?? $answer->selected_option_id,
                'text_answer' => $data['text_answer'] ?? $answer->text_answer,
                'answer_data' => $data['answer_data'] ?? $answer->answer_data,
                'time_spent_seconds' => $data['time_spent_seconds'] ?? $answer->time_spent_seconds,
                'play_count' => $data['play_count'] ?? $answer->play_count
            ]);

            // Re-evaluate the answer
            $this->evaluateAnswer($answer);

            return $answer->load(['question', 'selectedOption']);
        });
    }

    /**
     * Evaluate answer correctness and assign points
     */
    private function evaluateAnswer(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        
        switch ($question->question_type) {
            case 'multiple_choice':
                $this->evaluateMultipleChoice($answer);
                break;
            case 'multiple_answer':
                $this->evaluateMultipleAnswer($answer);
                break;
            case 'true_false_not_given':
                $this->evaluateTrueFalseNotGiven($answer);
                break;
            case 'short_answer':
                $this->evaluateShortAnswer($answer);
                break;
            case 'audio_dictation':
                $this->evaluateAudioDictation($answer);
                break;
            case 'sentence_completion':
                $this->evaluateSentenceCompletion($answer);
                break;
            case 'gap_fill_listening':
                $this->evaluateGapFillListening($answer);
                break;
            case 'summary_completion':
                $this->evaluateSummaryCompletion($answer);
                break;
            case 'note_completion':
                $this->evaluateNoteCompletion($answer);
                break;
            case 'table_completion':
                $this->evaluateTableCompletion($answer);
                break;
            case 'form_completion':
                $this->evaluateFormCompletion($answer);
                break;
            case 'flowchart_completion':
                $this->evaluateFlowchartCompletion($answer);
                break;
            case 'matching_labeling':
                $this->evaluateMatchingLabeling($answer);
                break;
            case 'classification':
                $this->evaluateClassification($answer);
                break;
            case 'diagram_labeling':
                $this->evaluateDiagramLabeling($answer);
                break;
            default:
                // For question types without automatic evaluation
                $answer->update([
                    'is_correct' => null,
                    'points_earned' => 0
                ]);
                break;
        }
    }

    /**
     * Evaluate multiple choice question
     */
    private function evaluateMultipleChoice(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctOption = $question->options()->where('is_correct', true)->first();
        
        $isCorrect = $answer->selected_option_id === $correctOption?->id;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points,
            'answer_explanation' => $correctOption?->explanation
        ]);
    }

    /**
     * Evaluate multiple select question
     */
    private function evaluateMultipleSelect(ListeningQuestionAnswer $answer): void
    {
        $this->evaluateMultipleAnswer($answer);
    }

    /**
     * Evaluate multiple answer question (QT2)
     */
    private function evaluateMultipleAnswer(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctOptions = $question->options()->where('is_correct', true)->pluck('id')->toArray();
        $selectedOptions = $answer->answer_data['selected_options'] ?? [];

        $isCorrect = count($correctOptions) === count($selectedOptions) && 
                    count(array_intersect($correctOptions, $selectedOptions)) === count($correctOptions);

        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate true/false/not given question (QT13)
     */
    private function evaluateTrueFalseNotGiven(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctOption = $question->options()->where('is_correct', true)->first();
        
        $isCorrect = $answer->selected_option_id === $correctOption?->id;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points,
            'answer_explanation' => $correctOption?->explanation
        ]);
    }

    /**
     * Evaluate short answer question (QT6)
     */
    private function evaluateShortAnswer(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctAnswers = $question->correct_answers ?? [];
        $userAnswer = trim(strtolower($answer->text_answer ?? ''));

        $isCorrect = false;
        foreach ($correctAnswers as $correctAnswer) {
            if (strtolower(trim($correctAnswer)) === $userAnswer) {
                $isCorrect = true;
                break;
            }
        }

        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate audio dictation question (QT15)
     */
    private function evaluateAudioDictation(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctText = $question->question_data['correct_text'] ?? '';
        $userAnswer = trim($answer->text_answer ?? '');

        // Use similarity for dictation (allowing minor mistakes)
        $similarity = 0;
        similar_text(strtolower($correctText), strtolower($userAnswer), $similarity);
        
        // Consider correct if 85% or higher similarity
        $isCorrect = $similarity >= 85;
        $points = $isCorrect ? ($question->points ?? 1) : ($similarity / 100) * ($question->points ?? 1);

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate gap fill listening question (QT14)
     */
    private function evaluateGapFillListening(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $gaps = $answer->answer_data['gaps'] ?? [];
        $correctGaps = $question->question_data['gaps'] ?? [];

        $totalGaps = count($correctGaps);
        $correctCount = 0;

        foreach ($gaps as $gapIndex => $userAnswer) {
            if (isset($correctGaps[$gapIndex])) {
                $correctAnswer = strtolower(trim($correctGaps[$gapIndex]['correct_answer'] ?? ''));
                $userAnswer = strtolower(trim($userAnswer));
                
                if ($correctAnswer === $userAnswer) {
                    $correctCount++;
                }
            }
        }

        $isCorrect = $totalGaps > 0 && $correctCount === $totalGaps;
        $points = $totalGaps > 0 ? ($correctCount / $totalGaps) * ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate form completion question (QT7)
     */
    private function evaluateFormCompletion(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $fields = $answer->answer_data['fields'] ?? [];
        $correctFields = $question->question_data['fields'] ?? [];

        $totalFields = count($correctFields);
        $correctCount = 0;

        foreach ($fields as $fieldName => $userAnswer) {
            if (isset($correctFields[$fieldName])) {
                $correctAnswers = is_array($correctFields[$fieldName]['correct_answers']) 
                    ? $correctFields[$fieldName]['correct_answers'] 
                    : [$correctFields[$fieldName]['correct_answer']];

                $userAnswer = strtolower(trim($userAnswer));
                foreach ($correctAnswers as $correctAnswer) {
                    if (strtolower(trim($correctAnswer)) === $userAnswer) {
                        $correctCount++;
                        break;
                    }
                }
            }
        }

        $isCorrect = $totalFields > 0 && $correctCount === $totalFields;
        $points = $totalFields > 0 ? ($correctCount / $totalFields) * ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate flowchart completion question (QT9)
     */
    private function evaluateFlowchartCompletion(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $nodes = $answer->answer_data['nodes'] ?? [];
        $correctNodes = $question->question_data['nodes'] ?? [];

        $totalNodes = count($correctNodes);
        $correctCount = 0;

        foreach ($nodes as $nodeId => $userAnswer) {
            if (isset($correctNodes[$nodeId])) {
                $correctAnswer = strtolower(trim($correctNodes[$nodeId]['correct_answer'] ?? ''));
                $userAnswer = strtolower(trim($userAnswer));
                
                if ($correctAnswer === $userAnswer) {
                    $correctCount++;
                }
            }
        }

        $isCorrect = $totalNodes > 0 && $correctCount === $totalNodes;
        $points = $totalNodes > 0 ? ($correctCount / $totalNodes) * ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate matching/labeling question (QT3)
     */
    private function evaluateMatchingLabeling(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $matches = $answer->answer_data['matches'] ?? [];
        $correctMatches = $question->question_data['correct_matches'] ?? [];

        $isCorrect = $this->compareMatches($matches, $correctMatches);
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate classification question (QT12)
     */
    private function evaluateClassification(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $classifications = $answer->answer_data['classifications'] ?? [];
        $correctClassifications = $question->question_data['correct_classifications'] ?? [];

        $totalItems = count($correctClassifications);
        $correctCount = 0;

        foreach ($classifications as $itemId => $category) {
            if (isset($correctClassifications[$itemId])) {
                if (strtolower(trim($category)) === strtolower(trim($correctClassifications[$itemId]))) {
                    $correctCount++;
                }
            }
        }

        $isCorrect = $totalItems > 0 && $correctCount === $totalItems;
        $points = $totalItems > 0 ? ($correctCount / $totalItems) * ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate diagram labeling question (QT11)
     */
    private function evaluateDiagramLabeling(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $labels = $answer->answer_data['labels'] ?? [];
        $correctLabels = $question->question_data['labels'] ?? [];

        $totalLabels = count($correctLabels);
        $correctCount = 0;

        foreach ($labels as $labelId => $userLabel) {
            if (isset($correctLabels[$labelId])) {
                $correctAnswers = is_array($correctLabels[$labelId]['correct_answers']) 
                    ? $correctLabels[$labelId]['correct_answers'] 
                    : [$correctLabels[$labelId]['correct_answer']];

                $userLabel = strtolower(trim($userLabel));
                foreach ($correctAnswers as $correctAnswer) {
                    if (strtolower(trim($correctAnswer)) === $userLabel) {
                        $correctCount++;
                        break;
                    }
                }
            }
        }

        $isCorrect = $totalLabels > 0 && $correctCount === $totalLabels;
        $points = $totalLabels > 0 ? ($correctCount / $totalLabels) * ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => round($points, 2)
        ]);
    }

    /**
     * Evaluate true/false question
     */
    private function evaluateTrueFalse(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctOption = $question->options()->where('is_correct', true)->first();
        
        $isCorrect = $answer->selected_option_id === $correctOption?->id;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points,
            'answer_explanation' => $correctOption?->explanation
        ]);
    }

    /**
     * Evaluate fill in the blank question
     */
    private function evaluateFillBlank(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $correctAnswers = $question->correct_answers ?? [];
        $userAnswer = trim(strtolower($answer->text_answer ?? ''));

        $isCorrect = false;
        foreach ($correctAnswers as $correctAnswer) {
            if (strtolower(trim($correctAnswer)) === $userAnswer) {
                $isCorrect = true;
                break;
            }
        }

        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate gap fill dropdown question
     */
    private function evaluateGapFillDropdown(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $gaps = $answer->answer_data['gaps'] ?? [];
        $correctGaps = $question->question_data['gaps'] ?? [];

        $totalGaps = count($correctGaps);
        $correctCount = 0;

        foreach ($gaps as $gapIndex => $selectedOptionId) {
            if (isset($correctGaps[$gapIndex])) {
                $correctOption = $question->options()
                    ->where('option_data->gap_index', $gapIndex)
                    ->where('is_correct', true)
                    ->first();
                
                if ($correctOption && $correctOption->id === $selectedOptionId) {
                    $correctCount++;
                }
            }
        }

        $isCorrect = $totalGaps > 0 && $correctCount === $totalGaps;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate match headings question
     */
    private function evaluateMatchHeadings(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $matches = $answer->answer_data['matches'] ?? [];
        $correctMatches = $question->question_data['correct_matches'] ?? [];

        $isCorrect = $this->compareMatches($matches, $correctMatches);
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate summary completion question
     */
    private function evaluateSummaryCompletion(ListeningQuestionAnswer $answer): void
    {
        $this->evaluateFillBlank($answer); // Similar logic to fill blank
    }

    /**
     * Evaluate note completion question
     */
    private function evaluateNoteCompletion(ListeningQuestionAnswer $answer): void
    {
        $this->evaluateFillBlank($answer); // Similar logic to fill blank
    }

    /**
     * Evaluate table completion question
     */
    private function evaluateTableCompletion(ListeningQuestionAnswer $answer): void
    {
        $question = $answer->question;
        $cells = $answer->answer_data['cells'] ?? [];
        $correctCells = $question->question_data['correct_cells'] ?? [];

        $totalCells = count($correctCells);
        $correctCount = 0;

        foreach ($cells as $cellKey => $cellValue) {
            if (isset($correctCells[$cellKey])) {
                $userAnswer = trim(strtolower($cellValue));
                $correctAnswers = is_array($correctCells[$cellKey]) 
                    ? $correctCells[$cellKey] 
                    : [$correctCells[$cellKey]];

                foreach ($correctAnswers as $correctAnswer) {
                    if (strtolower(trim($correctAnswer)) === $userAnswer) {
                        $correctCount++;
                        break;
                    }
                }
            }
        }

        $isCorrect = $totalCells > 0 && $correctCount === $totalCells;
        $points = $isCorrect ? ($question->points ?? 1) : 0;

        $answer->update([
            'is_correct' => $isCorrect,
            'points_earned' => $points
        ]);
    }

    /**
     * Evaluate sentence completion question
     */
    private function evaluateSentenceCompletion(ListeningQuestionAnswer $answer): void
    {
        $this->evaluateFillBlank($answer); // Similar logic to fill blank
    }

    /**
     * Compare two sets of matches
     */
    private function compareMatches(array $userMatches, array $correctMatches): bool
    {
        if (count($userMatches) !== count($correctMatches)) {
            return false;
        }

        foreach ($userMatches as $key => $value) {
            if (!isset($correctMatches[$key]) || $correctMatches[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get answer statistics for a submission
     */
    public function getAnswerStats(ListeningSubmission $submission): array
    {
        $answers = $submission->answers;
        
        return [
            'total_questions' => $answers->count(),
            'answered_questions' => $answers->whereNotNull('selected_option_id')
                ->orWhereNotNull('text_answer')->count(),
            'correct_answers' => $answers->where('is_correct', true)->count(),
            'total_points_earned' => $answers->sum('points_earned'),
            'total_possible_points' => $answers->sum(function ($answer) {
                return $answer->question->points ?? 1;
            }),
            'accuracy_percentage' => $answers->whereNotNull('is_correct')->count() > 0
                ? ($answers->where('is_correct', true)->count() / $answers->whereNotNull('is_correct')->count()) * 100
                : 0,
            'average_time_per_question' => $answers->where('time_spent_seconds', '>', 0)->avg('time_spent_seconds'),
            'total_audio_plays' => $answers->sum('play_count')
        ];
    }
}