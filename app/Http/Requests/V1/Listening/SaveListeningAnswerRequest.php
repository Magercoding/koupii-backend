<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class SaveListeningAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->route('submission')) {
            return $this->user()->id === $this->route('submission')->student_id;
        }
        
        if ($this->route('answer')) {
            return $this->user()->id === $this->route('answer')->submission->student_id;
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'question_id' => 'required|uuid|exists:test_questions,id',
            'selected_option_id' => 'nullable|uuid|exists:question_options,id',
            'text_answer' => 'nullable|string|max:2000',
            'answer_data' => 'nullable|array',
            'time_spent_seconds' => 'nullable|integer|min:0',
            'play_count' => 'nullable|integer|min:0',
        ];

        // Conditional validation based on question type
        if ($this->has('answer_data')) {
            $rules = array_merge($rules, [
                'answer_data.selected_options' => 'nullable|array',
                'answer_data.selected_options.*' => 'uuid|exists:question_options,id',
                'answer_data.gaps' => 'nullable|array',
                'answer_data.gaps.*' => 'nullable|string|max:200',
                'answer_data.matches' => 'nullable|array',
                'answer_data.cells' => 'nullable|array',
                'answer_data.cells.*' => 'nullable|string|max:200',
                'answer_data.sequence' => 'nullable|array',
                'answer_data.sequence.*' => 'integer',
                'answer_data.coordinates' => 'nullable|array',
                'answer_data.coordinates.*.x' => 'numeric',
                'answer_data.coordinates.*.y' => 'numeric',
                'answer_data.audio_timestamps' => 'nullable|array',
                'answer_data.audio_timestamps.*.start' => 'numeric|min:0',
                'answer_data.audio_timestamps.*.end' => 'numeric|min:0'
            ]);
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question_id' => 'question ID',
            'selected_option_id' => 'selected option',
            'text_answer' => 'text answer',
            'answer_data' => 'answer data',
            'time_spent_seconds' => 'time spent',
            'play_count' => 'play count',
            'answer_data.selected_options' => 'selected options',
            'answer_data.gaps' => 'gap answers',
            'answer_data.matches' => 'matches',
            'answer_data.cells' => 'table cells',
            'answer_data.sequence' => 'sequence order',
            'answer_data.coordinates' => 'coordinates',
            'answer_data.audio_timestamps' => 'audio timestamps'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required.',
            'question_id.exists' => 'The selected question does not exist.',
            'selected_option_id.exists' => 'The selected option does not exist.',
            'text_answer.max' => 'Text answer cannot exceed 2000 characters.',
            'time_spent_seconds.min' => 'Time spent cannot be negative.',
            'play_count.min' => 'Play count cannot be negative.',
            'answer_data.selected_options.*.exists' => 'One or more selected options do not exist.',
            'answer_data.gaps.*.max' => 'Each gap answer cannot exceed 200 characters.',
            'answer_data.cells.*.max' => 'Each cell answer cannot exceed 200 characters.',
            'answer_data.coordinates.*.x.numeric' => 'X coordinate must be a number.',
            'answer_data.coordinates.*.y.numeric' => 'Y coordinate must be a number.',
            'answer_data.audio_timestamps.*.start.min' => 'Audio timestamp start time cannot be negative.',
            'answer_data.audio_timestamps.*.end.min' => 'Audio timestamp end time cannot be negative.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up text answer
        if ($this->has('text_answer') && is_string($this->text_answer)) {
            $this->merge([
                'text_answer' => trim($this->text_answer)
            ]);
        }

        // Ensure play_count is not null
        if (!$this->has('play_count')) {
            $this->merge(['play_count' => 0]);
        }

        // Ensure time_spent_seconds is not null
        if (!$this->has('time_spent_seconds')) {
            $this->merge(['time_spent_seconds' => 0]);
        }
    }
}