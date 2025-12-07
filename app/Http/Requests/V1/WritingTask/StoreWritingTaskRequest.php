<?php

namespace App\Http\Requests\V1\WritingTask;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreWritingTaskRequest extends BaseRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'instructions' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'allow_retake' => 'boolean',
            'max_retake_attempts' => 'nullable|integer|min:1|max:5',
            'retake_options' => 'nullable|array',
            'retake_options.*' => Rule::in(['rewrite_all', 'group_similar', 'choose_any']),
            'timer_type' => ['required', Rule::in(['none', 'countdown', 'countup'])],
            'time_limit_seconds' => 'nullable|integer|min:300|max:28800', // 5 minutes to 8 hours
            'allow_submission_files' => 'boolean',
            'is_published' => 'boolean',
            'due_date' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'description.required' => 'Task description is required',
            'word_limit.min' => 'Word limit must be at least 50 words',
            'word_limit.max' => 'Word limit cannot exceed 5000 words',
            'max_retake_attempts.max' => 'Maximum retake attempts cannot exceed 5',
            'timer_type.required' => 'Timer type is required',
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
        $this->merge([
            'allow_retake' => $this->boolean('allow_retake'),
            'allow_submission_files' => $this->boolean('allow_submission_files'),
            'is_published' => $this->boolean('is_published'),
        ]);

        // Set default retake options if allow_retake is true
        if ($this->allow_retake && !$this->retake_options) {
            $this->merge([
                'retake_options' => ['rewrite_all', 'group_similar', 'choose_any']
            ]);
        }
    }
}
