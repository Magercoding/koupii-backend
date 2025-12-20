<?php

namespace App\Helpers\Listening;

use App\Models\TestQuestion;

class ListeningQuestionHelper
{
    // All 15 listening question types
    public const QUESTION_TYPES = [
        'QT1' => [
            'code' => 'multiple_choice',
            'name' => 'Multiple Choice',
            'description' => 'Choose one correct answer from multiple options',
            'validation_type' => 'single_option'
        ],
        'QT2' => [
            'code' => 'multiple_answer',
            'name' => 'Multiple Answer',
            'description' => 'Choose multiple correct answers from options',
            'validation_type' => 'multiple_options'
        ],
        'QT3' => [
            'code' => 'matching_labeling',
            'name' => 'Matching/Map/Plan Labeling',
            'description' => 'Match items or label diagrams/maps/plans',
            'validation_type' => 'matching'
        ],
        'QT4' => [
            'code' => 'table_completion',
            'name' => 'Table Completion',
            'description' => 'Fill in missing information in a table',
            'validation_type' => 'table_cells'
        ],
        'QT5' => [
            'code' => 'sentence_completion',
            'name' => 'Sentence Completion',
            'description' => 'Complete sentences with missing words/phrases',
            'validation_type' => 'text_gaps'
        ],
        'QT6' => [
            'code' => 'short_answer',
            'name' => 'Short Answer Question',
            'description' => 'Provide brief answers to questions',
            'validation_type' => 'short_text'
        ],
        'QT7' => [
            'code' => 'form_completion',
            'name' => 'Form Completion',
            'description' => 'Fill in forms with personal/specific information',
            'validation_type' => 'form_fields'
        ],
        'QT8' => [
            'code' => 'note_completion',
            'name' => 'Note Completion',
            'description' => 'Complete notes with missing information',
            'validation_type' => 'note_gaps'
        ],
        'QT9' => [
            'code' => 'flowchart_completion',
            'name' => 'Flowchart Completion',
            'description' => 'Complete flowcharts with missing steps/information',
            'validation_type' => 'flowchart_nodes'
        ],
        'QT10' => [
            'code' => 'summary_completion',
            'name' => 'Summary Completion',
            'description' => 'Complete summary with missing words/phrases',
            'validation_type' => 'summary_gaps'
        ],
        'QT11' => [
            'code' => 'diagram_labeling',
            'name' => 'Diagram Labeling',
            'description' => 'Label parts of diagrams based on audio',
            'validation_type' => 'diagram_labels'
        ],
        'QT12' => [
            'code' => 'classification',
            'name' => 'Classification',
            'description' => 'Classify information into categories',
            'validation_type' => 'category_matching'
        ],
        'QT13' => [
            'code' => 'true_false_not_given',
            'name' => 'True/False/Not Given',
            'description' => 'Determine if statements are true, false, or not mentioned',
            'validation_type' => 'three_option'
        ],
        'QT14' => [
            'code' => 'gap_fill_listening',
            'name' => 'Gap Fill (Listening)',
            'description' => 'Fill gaps while listening to audio',
            'validation_type' => 'listening_gaps'
        ],
        'QT15' => [
            'code' => 'audio_dictation',
            'name' => 'Audio Dictation',
            'description' => 'Write exactly what you hear',
            'validation_type' => 'dictation'
        ]
    ];

    /**
     * Get correct answer text for display
     */
    public static function getCorrectAnswerText(TestQuestion $question): string
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'true_false_not_given':
                $correctOption = $question->options()->where('is_correct', true)->first();
                return $correctOption ? $correctOption->option_text : 'No correct answer set';

            case 'multiple_answer':
                $correctOptions = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctOptions->implode(', ');

            case 'short_answer':
            case 'audio_dictation':
                $correctAnswers = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctAnswers->implode(' / ');

            case 'matching_labeling':
            case 'classification':
                $matches = $question->questionBreakdowns()
                    ->with('questionGroup')
                    ->get()
                    ->map(function ($breakdown) {
                        return $breakdown->breakdown_text . ' → ' . $breakdown->questionGroup->correct_answer;
                    });
                return $matches->implode('; ');

            case 'table_completion':
            case 'form_completion':
            case 'note_completion':
            case 'flowchart_completion':
            case 'summary_completion':
            case 'sentence_completion':
            case 'gap_fill_listening':
                return static::getStructuredAnswerText($question);

            case 'diagram_labeling':
                return static::getDiagramLabelText($question);

            default:
                return 'Manual evaluation required';
        }
    }

    /**
     * Get structured answer text for completion-type questions
     */
    private static function getStructuredAnswerText(TestQuestion $question): string
    {
        $questionData = $question->question_data ?? [];
        $answers = [];

        if (isset($questionData['gaps'])) {
            foreach ($questionData['gaps'] as $gapIndex => $gapData) {
                $correctAnswer = $gapData['correct_answer'] ?? 'No answer';
                $answers[] = "Gap " . ($gapIndex + 1) . ": " . $correctAnswer;
            }
        } elseif (isset($questionData['fields'])) {
            foreach ($questionData['fields'] as $fieldName => $fieldData) {
                $correctAnswer = $fieldData['correct_answer'] ?? 'No answer';
                $answers[] = $fieldName . ": " . $correctAnswer;
            }
        } else {
            $correctOptions = $question->options()->where('is_correct', true)->pluck('option_text');
            return $correctOptions->implode(' / ');
        }

        return implode('; ', $answers);
    }

    /**
     * Get diagram label text
     */
    private static function getDiagramLabelText(TestQuestion $question): string
    {
        $questionData = $question->question_data ?? [];
        if (isset($questionData['labels'])) {
            $labels = [];
            foreach ($questionData['labels'] as $labelId => $labelData) {
                $labels[] = $labelId . ': ' . ($labelData['correct_answer'] ?? 'No label');
            }
            return implode('; ', $labels);
        }
        
        return 'No labels defined';
    }

    /**
     * Get question type specific validation rules
     */
    public static function getValidationRules(string $questionType): array
    {
        $typeInfo = collect(static::QUESTION_TYPES)->first(function($type) use ($questionType) {
            return $type['code'] === $questionType;
        });

        if (!$typeInfo) {
            return ['answer_data' => 'nullable|array'];
        }

        switch ($typeInfo['validation_type']) {
            case 'single_option':
                return [
                    'selected_option_id' => 'required|string|exists:question_options,id'
                ];

            case 'multiple_options':
                return [
                    'answer_data.selected_options' => 'required|array|min:1',
                    'answer_data.selected_options.*' => 'string|exists:question_options,id'
                ];

            case 'short_text':
            case 'dictation':
                return [
                    'text_answer' => 'required|string|max:500'
                ];

            case 'text_gaps':
            case 'listening_gaps':
            case 'summary_gaps':
            case 'note_gaps':
                return [
                    'answer_data.gaps' => 'required|array',
                    'answer_data.gaps.*' => 'string|max:200'
                ];

            case 'matching':
            case 'category_matching':
                return [
                    'answer_data.matches' => 'required|array',
                    'answer_data.matches.*' => 'string'
                ];

            case 'table_cells':
                return [
                    'answer_data.cells' => 'required|array',
                    'answer_data.cells.*' => 'string|max:200'
                ];

            case 'form_fields':
                return [
                    'answer_data.fields' => 'required|array',
                    'answer_data.fields.*' => 'string|max:200'
                ];

            case 'flowchart_nodes':
                return [
                    'answer_data.nodes' => 'required|array',
                    'answer_data.nodes.*' => 'string|max:200'
                ];

            case 'diagram_labels':
                return [
                    'answer_data.labels' => 'required|array',
                    'answer_data.labels.*' => 'string|max:100'
                ];

            case 'three_option':
                return [
                    'selected_option_id' => 'required|string|exists:question_options,id'
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
            case 'true_false_not_given':
                if (isset($answerData['selected_option_id'])) {
                    $option = $question->options()->find($answerData['selected_option_id']);
                    return $option ? $option->option_text : 'Invalid option';
                }
                return 'No answer selected';

            case 'multiple_answer':
                if (isset($answerData['answer_data']['selected_options'])) {
                    $selectedIds = $answerData['answer_data']['selected_options'];
                    $options = $question->options()->whereIn('id', $selectedIds)->pluck('option_text');
                    return $options->implode(', ');
                }
                return 'No options selected';

            case 'short_answer':
            case 'audio_dictation':
                return $answerData['text_answer'] ?? 'No answer provided';

            case 'sentence_completion':
            case 'gap_fill_listening':
            case 'summary_completion':
            case 'note_completion':
                return static::formatGapAnswers($answerData);

            case 'table_completion':
                return static::formatTableAnswers($answerData);

            case 'form_completion':
                return static::formatFormAnswers($answerData);

            case 'flowchart_completion':
                return static::formatFlowchartAnswers($answerData);

            case 'matching_labeling':
            case 'classification':
                return static::formatMatchingAnswers($answerData);

            case 'diagram_labeling':
                return static::formatDiagramAnswers($answerData);

            default:
                return json_encode($answerData);
        }
    }

    /**
     * Format gap-fill type answers
     */
    private static function formatGapAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['gaps'])) {
            $gaps = $answerData['answer_data']['gaps'];
            $formatted = [];
            foreach ($gaps as $gapIndex => $gapValue) {
                $formatted[] = "Gap " . ($gapIndex + 1) . ": " . $gapValue;
            }
            return implode('; ', $formatted);
        }
        return 'No gaps filled';
    }

    /**
     * Format table completion answers
     */
    private static function formatTableAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['cells'])) {
            $cells = $answerData['answer_data']['cells'];
            $formatted = [];
            foreach ($cells as $cellKey => $cellValue) {
                $formatted[] = $cellKey . ': ' . $cellValue;
            }
            return implode('; ', $formatted);
        }
        return 'No cells completed';
    }

    /**
     * Format form completion answers
     */
    private static function formatFormAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['fields'])) {
            $fields = $answerData['answer_data']['fields'];
            $formatted = [];
            foreach ($fields as $fieldName => $fieldValue) {
                $formatted[] = $fieldName . ': ' . $fieldValue;
            }
            return implode('; ', $formatted);
        }
        return 'No form fields completed';
    }

    /**
     * Format flowchart answers
     */
    private static function formatFlowchartAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['nodes'])) {
            $nodes = $answerData['answer_data']['nodes'];
            $formatted = [];
            foreach ($nodes as $nodeId => $nodeValue) {
                $formatted[] = "Node " . $nodeId . ': ' . $nodeValue;
            }
            return implode(' → ', $formatted);
        }
        return 'No flowchart nodes completed';
    }

    /**
     * Format matching/classification answers
     */
    private static function formatMatchingAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['matches'])) {
            $matches = $answerData['answer_data']['matches'];
            $formatted = [];
            foreach ($matches as $item => $match) {
                $formatted[] = $item . ' → ' . $match;
            }
            return implode('; ', $formatted);
        }
        return 'No matches made';
    }

    /**
     * Format diagram labeling answers
     */
    private static function formatDiagramAnswers($answerData): string
    {
        if (isset($answerData['answer_data']['labels'])) {
            $labels = $answerData['answer_data']['labels'];
            $formatted = [];
            foreach ($labels as $labelId => $labelValue) {
                $formatted[] = "Label " . $labelId . ': ' . $labelValue;
            }
            return implode('; ', $formatted);
        }
        return 'No labels completed';
    }

    /**
     * Get question type by code
     */
    public static function getQuestionTypeByCode(string $code): ?array
    {
        return static::QUESTION_TYPES[$code] ?? null;
    }

    /**
     * Get all question type codes
     */
    public static function getAllQuestionTypeCodes(): array
    {
        return array_keys(static::QUESTION_TYPES);
    }

    /**
     * Get question types that require audio interaction
     */
    public static function getAudioInteractionTypes(): array
    {
        return [
            'QT14', // gap_fill_listening
            'QT15', // audio_dictation
        ];
    }

    /**
     * Check if question type requires real-time audio interaction
     */
    public static function requiresAudioInteraction(string $questionType): bool
    {
        $audioTypes = [
            'gap_fill_listening',
            'audio_dictation'
        ];
        
        return in_array($questionType, $audioTypes);
    }
}