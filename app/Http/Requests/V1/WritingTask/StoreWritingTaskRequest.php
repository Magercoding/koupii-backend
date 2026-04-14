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
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',  // Task 8.4: changed from required to nullable
            'difficulty'  => 'required|string|in:beginner,elementary,intermediate,advanced',
            'instructions' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'allow_retake' => 'boolean',
            'max_retake_attempts' => 'nullable|integer|min:1|max:5',
            'retake_options' => 'nullable|array',
            'retake_options.*' => Rule::in(['rewrite_all', 'group_similar', 'choose_any']),
            'timer_mode'     => 'nullable|string|in:none,countdown,countup',
            'timer_settings' => 'nullable',
            'timer_type' => ['nullable', Rule::in(['none', 'countdown', 'countup'])],
            'time_limit_seconds' => 'nullable|integer|min:300|max:28800',
            'allow_submission_files' => 'boolean',
            'is_published' => 'boolean',
            'class_id'    => 'nullable|uuid|exists:classes,id',
            'due_date'    => 'nullable|date|after:now',
            'max_repetition_count' => 'nullable|integer|min:1',

            // Passages (required, min 1) — Task 8.4
            'passages'                              => 'required|array|min:1',
            'passages.*.title'                      => 'required|string|max:255',
            'passages.*.description'                => 'nullable|string',
            'passages.*.image_context'              => 'nullable|file|mimes:jpeg,png,webp|max:5120',
            'passages.*.questions'                  => 'required|array|min:1',
            'passages.*.questions.*.question_text'  => 'required|string|max:2000',
            'passages.*.questions.*.question_number'=> 'nullable|integer',

            // Legacy questions support (optional)
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
            'title.required'              => 'Task title is required',
            'difficulty.required'         => 'Difficulty level is required',
            'difficulty.in'               => 'Invalid difficulty level',
            'passages.required'           => 'At least one passage is required',
            'passages.min'                => 'At least one passage is required',
            'passages.*.title.required'   => 'Each passage must have a title',
            'passages.*.questions.required' => 'Each passage must have at least one question',
            'passages.*.questions.min'    => 'Each passage must have at least one question',
            'passages.*.image_context.mimes' => 'Passage image must be a JPEG, PNG, or WEBP file',
            'passages.*.image_context.max'   => 'Passage image must not exceed 5 MB',
            'word_limit.min'              => 'Word limit must be at least 50 words',
            'word_limit.max'              => 'Word limit cannot exceed 5000 words',
            'max_retake_attempts.max'     => 'Maximum retake attempts cannot exceed 5',
            'time_limit_seconds.min'      => 'Time limit must be at least 5 minutes',
            'time_limit_seconds.max'      => 'Time limit cannot exceed 8 hours',
            'due_date.after'              => 'Due date must be in the future',
            'retake_options.*.in'         => 'Invalid retake option selected',
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

        // Only parse JSON strings if they are actually strings (for multipart/form-data)
        if ($this->has('passages') && is_string($this->input('passages'))) {
            $this->merge([
                'passages' => json_decode($this->input('passages'), true)
            ]);
        }

        if ($this->has('questions') && is_string($this->input('questions'))) {
            $this->merge([
                'questions' => json_decode($this->input('questions'), true)
            ]);
        }

        if ($this->has('timer_settings') && is_string($this->input('timer_settings'))) {
            $this->merge([
                'timer_settings' => json_decode($this->input('timer_settings'), true)
            ]);
        }

        if ($this->has('retake_options') && is_string($this->input('retake_options'))) {
            $this->merge([
                'retake_options' => json_decode($this->input('retake_options'), true)
            ]);
        }

        if ($this->has('classroom_assignments') && is_string($this->input('classroom_assignments'))) {
            $this->merge([
                'classroom_assignments' => json_decode($this->input('classroom_assignments'), true)
            ]);
        }

        // Set default retake options if allow_retake is true and no options provided
        if ($this->boolean('allow_retake') && (!$this->input('retake_options') || empty($this->input('retake_options')))) {
            $this->merge([
                'retake_options' => ['rewrite_all', 'group_similar', 'choose_any']
            ]);
        }
    }
}
