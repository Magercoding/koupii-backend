<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class StoreListeningTaskRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string|max:2000',
            'timer_type' => 'required|in:no_timer,fixed_timer,countdown_timer',
            'time_limit_seconds' => 'nullable|integer|min:1|max:10800', // Max 3 hours
            'difficulty_level' => 'required|in:beginner,elementary,intermediate,advanced',
            'retakes_allowed' => 'boolean',
            'max_retakes' => 'nullable|integer|min:0|max:10',
            'auto_mark' => 'boolean',
            'feedback_enabled' => 'boolean',
            'is_published' => 'boolean',
            'audio_file_url' => 'nullable|string|url',
            'transcript' => 'nullable|string',
            'question_data' => 'nullable|array',
            'question_data.*.question_type' => 'required|string|in:QT1,QT2,QT3,QT4,QT5,QT6,QT7,QT8,QT9,QT10,QT11,QT12,QT13,QT14,QT15',
            'question_data.*.question_text' => 'required|string',
            'question_data.*.options' => 'nullable|array',
            'question_data.*.correct_answer' => 'nullable|string',
            'question_data.*.points' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'task title',
            'description' => 'task description',
            'instructions' => 'task instructions',
            'timer_type' => 'timer type',
            'time_limit_seconds' => 'time limit',
            'difficulty_level' => 'difficulty level',
            'retakes_allowed' => 'retakes allowed',
            'max_retakes' => 'maximum retakes',
            'auto_mark' => 'auto marking',
            'feedback_enabled' => 'feedback enabled',
            'is_published' => 'publication status',
            'audio_file_url' => 'audio file URL',
            'transcript' => 'audio transcript',
            'question_data' => 'question data',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'timer_type.in' => 'Timer type must be one of: no_timer, fixed_timer, countdown_timer',
            'difficulty_level.in' => 'Difficulty level must be one of: beginner, elementary, intermediate, advanced',
            'time_limit_seconds.max' => 'Time limit cannot exceed 3 hours (10800 seconds)',
            'max_retakes.max' => 'Maximum retakes cannot exceed 10',
            'question_data.*.question_type.in' => 'Question type must be one of the valid listening question types (QT1-QT15)',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'creator_id' => $this->user()->id,
            'retakes_allowed' => $this->boolean('retakes_allowed'),
            'auto_mark' => $this->boolean('auto_mark'),
            'feedback_enabled' => $this->boolean('feedback_enabled'),
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}