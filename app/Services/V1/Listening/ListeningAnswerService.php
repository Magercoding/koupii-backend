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
            case 'multiple_select':
                $this->evaluateMultipleSelect($answer);
                break;
            case 'true_false':
                $this->evaluateTrueFalse($answer);
                break;
            case 'fill_blank':
                $this->evaluateFillBlank($answer);
                break;
            case 'gap_fill_dropdown':
                $this->evaluateGapFillDropdown($answer);
                break;
            case 'match_headings':
                $this->evaluateMatchHeadings($answer);
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
            case 'sentence_completion':
                $this->evaluateSentenceCompletion($answer);
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