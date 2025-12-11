<?php

namespace App\Helpers\Listening;

use App\Models\TestQuestion;

class ListeningQuestionHelper
{
    /**
     * Get correct answer text for display
     */
    public static function getCorrectAnswerText(TestQuestion $question): string
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'single_correct':
            case 'true_false':
                $correctOption = $question->options()->where('is_correct', true)->first();
                return $correctOption ? $correctOption->option_text : 'No correct answer set';

            case 'multiple_correct':
                $correctOptions = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctOptions->implode(', ');

            case 'fill_in_the_blank':
            case 'short_answer':
            case 'listening_comprehension':
            case 'audio_dictation':
                $correctAnswers = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctAnswers->implode(' / ');

            case 'matching':
                $matches = $question->questionBreakdowns()
                    ->with('questionGroup')
                    ->get()
                    ->map(function ($breakdown) {
                        return $breakdown->breakdown_text . ' -> ' . $breakdown->questionGroup->correct_answer;
                    });
                return $matches->implode('; ');

            case 'ordering':
            case 'drag_drop':
                $correctOrder = $question->options()
                    ->orderBy('display_order')
                    ->pluck('option_text');
                return $correctOrder->implode(' → ');

            default:
                return 'Manual evaluation required';
        }
    }

    /**
     * Get question type specific validation rules
     */
    public static function getValidationRules(string $questionType): array
    {
        switch ($questionType) {
            case 'multiple_choice':
            case 'single_correct':
            case 'true_false':
                return [
                    'selected_option_id' => 'required|string|exists:question_options,id'
                ];

            case 'multiple_correct':
                return [
                    'answer_data.selected_options' => 'required|array|min:1',
                    'answer_data.selected_options.*' => 'string|exists:question_options,id'
                ];

            case 'fill_in_the_blank':
            case 'short_answer':
            case 'listening_comprehension':
            case 'audio_dictation':
                return [
                    'text_answer' => 'required|string|max:500'
                ];

            case 'gap_fill_dropdown':
                return [
                    'answer_data.gaps' => 'required|array',
                    'answer_data.gaps.*' => 'string|exists:question_options,id'
                ];

            case 'matching':
                return [
                    'answer_data.matches' => 'required|array',
                    'answer_data.matches.*' => 'string'
                ];

            case 'table_completion':
                return [
                    'answer_data.cells' => 'required|array',
                    'answer_data.cells.*' => 'string|max:200'
                ];

            default:
                return [
                    'answer_data' => 'nullable|array'
                ];
        }
    }

    /**
     * Format answer for display
     */
    public static function formatAnswerForDisplay(TestQuestion $question, $answerData): string
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'single_correct':
            case 'true_false':
                if (isset($answerData['selected_option_id'])) {
                    $option = $question->options()->find($answerData['selected_option_id']);
                    return $option ? $option->option_text : 'Invalid option';
                }
                return 'No answer selected';

            case 'multiple_correct':
                if (isset($answerData['answer_data']['selected_options'])) {
                    $selectedIds = $answerData['answer_data']['selected_options'];
                    $options = $question->options()->whereIn('id', $selectedIds)->pluck('option_text');
                    return $options->implode(', ');
                }
                return 'No options selected';

            case 'fill_in_the_blank':
            case 'short_answer':
            case 'listening_comprehension':
            case 'audio_dictation':
                return $answerData['text_answer'] ?? 'No answer provided';

            case 'gap_fill_dropdown':
                if (isset($answerData['answer_data']['gaps'])) {
                    $gaps = $answerData['answer_data']['gaps'];
                    $formatted = [];
                    foreach ($gaps as $gapIndex => $optionId) {
                        $option = $question->options()->find($optionId);
                        $formatted[] = "Gap " . ($gapIndex + 1) . ": " . ($option ? $option->option_text : 'Invalid');
                    }
                    return implode('; ', $formatted);
                }
                return 'No gaps filled';

            case 'matching':
                if (isset($answerData['answer_data']['matches'])) {
                    $matches = $answerData['answer_data']['matches'];
                    $formatted = [];
                    foreach ($matches as $item => $match) {
                        $formatted[] = $item . ' → ' . $match;
                    }
                    return implode('; ', $formatted);
                }
                return 'No matches made';

            case 'table_completion':
                if (isset($answerData['answer_data']['cells'])) {
                    $cells = $answerData['answer_data']['cells'];
                    $formatted = [];
                    foreach ($cells as $cellKey => $cellValue) {
                        $formatted[] = $cellKey . ': ' . $cellValue;
                    }
                    return implode('; ', $formatted);
                }
                return 'No cells completed';

            default:
                return json_encode($answerData);
        }
    }
}