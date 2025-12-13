<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'question_type_info' => $this->getQuestionTypeInfo(),
            'options' => $this->options,
            'correct_answer' => $this->when(
                $request->user()->hasRole(['admin', 'teacher']),
                $this->correct_answer
            ),
            'points' => $this->points,
            'order' => $this->order,
            'time_limit' => $this->time_limit,
            'audio_segment' => $this->audio_segment,
            'instructions' => $this->instructions,
            'explanation' => $this->when(
                $request->user()->hasRole(['admin', 'teacher']),
                $this->explanation
            ),
            'question_options' => $this->whenLoaded('questionOptions', function () {
                return $this->questionOptions->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'option_value' => $option->option_value,
                        'order' => $option->order,
                        'is_correct' => $this->when(
                            request()->user()->hasRole(['admin', 'teacher']),
                            $option->is_correct
                        )
                    ];
                });
            }),
            'audio_segments' => $this->whenLoaded('audioSegments', function () {
                return $this->audioSegments->map(function ($segment) {
                    return [
                        'id' => $segment->id,
                        'start_time' => $segment->start_time,
                        'end_time' => $segment->end_time,
                        'duration' => $segment->duration,
                        'transcript' => $segment->transcript,
                        'audio_url' => $segment->audio_url,
                        'order' => $segment->order
                    ];
                });
            }),
            'difficulty_level' => $this->difficulty_level,
            'validation_status' => $this->when(
                $request->user()->hasRole(['admin', 'teacher']),
                $this->getValidationStatus()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get question type information
     */
    private function getQuestionTypeInfo(): array
    {
        $questionTypes = [
            'QT1' => ['name' => 'Multiple Choice', 'code' => 'multiple_choice'],
            'QT2' => ['name' => 'Multiple Answer', 'code' => 'multiple_answer'],
            'QT3' => ['name' => 'Matching/Map/Plan Labeling', 'code' => 'matching_labeling'],
            'QT4' => ['name' => 'Table Completion', 'code' => 'table_completion'],
            'QT5' => ['name' => 'Sentence Completion', 'code' => 'sentence_completion'],
            'QT6' => ['name' => 'Short Answer Question', 'code' => 'short_answer'],
            'QT7' => ['name' => 'Form Completion', 'code' => 'form_completion'],
            'QT8' => ['name' => 'Note Completion', 'code' => 'note_completion'],
            'QT9' => ['name' => 'Flowchart Completion', 'code' => 'flowchart_completion'],
            'QT10' => ['name' => 'Summary Completion', 'code' => 'summary_completion'],
            'QT11' => ['name' => 'Diagram Labeling', 'code' => 'diagram_labeling'],
            'QT12' => ['name' => 'Classification', 'code' => 'classification'],
            'QT13' => ['name' => 'True/False/Not Given', 'code' => 'true_false_not_given'],
            'QT14' => ['name' => 'Gap Fill (Listening)', 'code' => 'gap_fill_listening'],
            'QT15' => ['name' => 'Audio Dictation', 'code' => 'audio_dictation']
        ];

        return $questionTypes[$this->question_type] ?? [
            'name' => 'Unknown Type',
            'code' => 'unknown'
        ];
    }

    /**
     * Get validation status for question
     */
    private function getValidationStatus(): array
    {
        $errors = [];
        $warnings = [];

        // Basic validation
        if (empty($this->question_text)) {
            $errors[] = 'Question text is missing';
        }

        if (empty($this->correct_answer)) {
            $errors[] = 'Correct answer is missing';
        }

        // Question type specific validation
        switch ($this->question_type) {
            case 'QT1':
            case 'QT2':
                if (empty($this->options)) {
                    $errors[] = 'Multiple choice questions require options';
                }
                break;
            
            case 'QT14':
            case 'QT15':
                if (empty($this->audio_segment)) {
                    $warnings[] = 'Audio segment recommended for this question type';
                }
                break;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}