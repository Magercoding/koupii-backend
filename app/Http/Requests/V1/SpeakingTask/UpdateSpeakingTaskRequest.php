<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class UpdateSpeakingTaskRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $speakingTask = $this->route('speakingTask');
        return $this->user()->can('update', $speakingTask);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string|max:2000',
            'difficulty' => 'sometimes|required|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'timer_type' => 'sometimes|required|string|in:none,per_question,total_test',
            'time_limit_seconds' => 'nullable|integer|min:60|max:7200',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:5',
            'is_published' => 'boolean',
            
            // Speaking sections (optional for updates)
            'sections' => 'sometimes|required|array|min:1|max:10',
            'sections.*.id' => 'nullable|uuid|exists:speaking_sections,id',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.instructions' => 'nullable|string|max:1000',
            'sections.*.order_index' => 'required|integer|min:0',
            'sections.*.time_limit_seconds' => 'nullable|integer|min:30|max:1800',
            
            // Speaking questions (optional for updates)
            'sections.*.questions' => 'required|array|min:1|max:20',
            'sections.*.questions.*.id' => 'nullable|uuid|exists:speaking_questions,id',
            'sections.*.questions.*.topic' => 'required|string|max:255',
            'sections.*.questions.*.prompt' => 'required|string|max:2000',
            'sections.*.questions.*.preparation_time_seconds' => 'nullable|integer|min:15|max:300',
            'sections.*.questions.*.response_time_seconds' => 'required|integer|min:30|max:300',
            'sections.*.questions.*.order_index' => 'required|integer|min:0',
            'sections.*.questions.*.sample_answer' => 'nullable|string|max:2000',
            'sections.*.questions.*.evaluation_criteria' => 'nullable|array',
            'sections.*.questions.*.evaluation_criteria.*' => 'string|max:500'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The speaking task title is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'difficulty.required' => 'The difficulty level is required.',
            'difficulty.in' => 'The difficulty must be one of: beginner, elementary, intermediate, upper_intermediate, advanced, proficiency.',
            'timer_type.required' => 'The timer type is required.',
            'timer_type.in' => 'The timer type must be one of: none, per_question, total_test.',
            'time_limit_seconds.min' => 'Time limit must be at least 1 minute (60 seconds).',
            'time_limit_seconds.max' => 'Time limit may not exceed 2 hours (7200 seconds).',
            
            'sections.required' => 'At least one speaking section is required.',
            'sections.min' => 'At least one speaking section is required.',
            'sections.max' => 'Maximum 10 sections allowed per speaking task.',
            'sections.*.questions.required' => 'At least one question is required per section.',
            'sections.*.questions.min' => 'At least one question is required per section.',
            'sections.*.questions.max' => 'Maximum 20 questions allowed per section.'
        ];
    }
}