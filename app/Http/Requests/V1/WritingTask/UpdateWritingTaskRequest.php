<?php

namespace App\Http\Requests\V1\WritingTask;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWritingTaskRequest extends FormRequest
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
            'due_date' => 'nullable|date',
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
            'retake_options.*.in' => 'Invalid retake option selected',
        ];
    }
}
