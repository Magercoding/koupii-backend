<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class SubmitListeningAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'question_id' => [
                'required',
                'string',
                'exists:test_questions,id'
            ],
            'selected_option_id' => [
                'nullable',
                'string',
                'exists:question_options,id'
            ],
            'text_answer' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'answer_data' => [
                'nullable',
                'array'
            ],
            'answer_data.selected_options' => [
                'nullable',
                'array'
            ],
            'answer_data.selected_options.*' => [
                'string',
                'exists:question_options,id'
            ],
            'answer_data.matches' => [
                'nullable',
                'array'
            ],
            'answer_data.order' => [
                'nullable',
                'array'
            ],
            'answer_data.highlighted_text' => [
                'nullable',
                'array'
            ],
            'time_spent_seconds' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'play_count' => [
                'nullable',
                'integer',
                'min:0'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required',
            'question_id.exists' => 'The specified question does not exist',
            'selected_option_id.exists' => 'The specified option does not exist',
            'text_answer.max' => 'Text answer cannot exceed 1000 characters',
            'answer_data.array' => 'Answer data must be an object',
            'answer_data.selected_options.array' => 'Selected options must be an array',
            'answer_data.selected_options.*.exists' => 'One or more selected options do not exist',
            'time_spent_seconds.integer' => 'Time spent must be a valid number',
            'time_spent_seconds.min' => 'Time spent cannot be negative',
            'play_count.integer' => 'Play count must be a valid number',
            'play_count.min' => 'Play count cannot be negative'
        ];
    }

    /**
     * Validate that at least one answer field is provided
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasAnswer = $this->selected_option_id || 
                        $this->text_answer || 
                        $this->answer_data;

            if (!$hasAnswer) {
                $validator->errors()->add(
                    'answer', 
                    'At least one answer field (selected_option_id, text_answer, or answer_data) must be provided'
                );
            }
        });
    }
}