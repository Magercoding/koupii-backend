<?php

namespace App\Http\Requests\V1\WritingTask;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWritingTaskRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'instructions' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'allow_retake' => 'sometimes|boolean',
            'max_retake_attempts' => 'nullable|integer|min:1|max:5',
            'retake_options' => 'nullable|array',
            'retake_options.*' => Rule::in(['rewrite_all', 'group_similar', 'choose_any']),
            'timer_type' => ['sometimes', Rule::in(['none', 'countdown', 'countup'])],
            'time_limit_seconds' => 'nullable|integer|min:300|max:28800',
            'allow_submission_files' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'due_date' => 'nullable|date|after:now',
            
            // Multiple questions support (similar to Reading/Listening)
            'questions' => 'nullable|array',
            'questions.*.question_type' => 'required_with:questions|string|in:essay,short_answer,creative_writing,argumentative,descriptive,narrative',
            'questions.*.question_text' => 'required_with:questions|string|max:2000',
            'questions.*.instructions' => 'nullable|string|max:1000',
            'questions.*.word_limit' => 'nullable|integer|min:50|max:5000',
            'questions.*.points' => 'nullable|numeric|min:0|max:100',
            'questions.*.rubric' => 'nullable|string',
            'questions.*.sample_answer' => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'word_limit.min' => 'Word limit must be at least 50 words',
            'word_limit.max' => 'Word limit cannot exceed 5000 words',
            'max_retake_attempts.max' => 'Maximum retake attempts cannot exceed 5',
            'timer_type.in' => 'Invalid timer type selected',
            'time_limit_seconds.min' => 'Time limit must be at least 5 minutes',
            'time_limit_seconds.max' => 'Time limit cannot exceed 8 hours',
            'due_date.after' => 'Due date must be in the future',
            'retake_options.*.in' => 'Invalid retake option selected',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean fields
        if ($this->has('allow_retake')) {
            $this->merge([
                'allow_retake' => $this->boolean('allow_retake'),
            ]);
        }

        if ($this->has('allow_submission_files')) {
            $this->merge([
                'allow_submission_files' => $this->boolean('allow_submission_files'),
            ]);
        }

        if ($this->has('is_published')) {
            $this->merge([
                'is_published' => $this->boolean('is_published'),
            ]);
        }

        // Only parse JSON strings if they are actually strings (for multipart/form-data)
        if ($this->has('questions') && is_string($this->questions)) {
            $this->merge([
                'questions' => json_decode($this->questions, true)
            ]);
        }

        if ($this->has('retake_options') && is_string($this->retake_options)) {
            $this->merge([
                'retake_options' => json_decode($this->retake_options, true)
            ]);
        }

        // Parse classroom_assignments if present
        if ($this->has('classroom_assignments') && is_string($this->classroom_assignments)) {
            $this->merge([
                'classroom_assignments' => json_decode($this->classroom_assignments, true)
            ]);
        }
    }
}
