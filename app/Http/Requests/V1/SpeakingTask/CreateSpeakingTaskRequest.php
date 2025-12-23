<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SpeakingTaskAssignment;

class CreateSpeakingTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', SpeakingTaskAssignment::class);
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
            'difficulty' => 'required|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'timer_type' => 'required|string|in:none,per_question,total_test',
            'time_limit_seconds' => 'nullable|integer|min:60|max:7200',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:5',
            'is_published' => 'boolean',
            
            // Speaking sections
            'sections' => 'required|array|min:1|max:10',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.instructions' => 'nullable|string|max:1000',
            'sections.*.order_index' => 'required|integer|min:0',
            'sections.*.time_limit_seconds' => 'nullable|integer|min:30|max:1800',
            
            // Speaking questions
            'sections.*.questions' => 'required|array|min:1|max:20',
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
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 5 attempts.',
            
            'sections.required' => 'At least one speaking section is required.',
            'sections.min' => 'At least one speaking section is required.',
            'sections.max' => 'Maximum 10 sections allowed per speaking task.',
            'sections.*.title.required' => 'Section title is required.',
            'sections.*.questions.required' => 'At least one question is required per section.',
            'sections.*.questions.min' => 'At least one question is required per section.',
            'sections.*.questions.max' => 'Maximum 20 questions allowed per section.',
            
            'sections.*.questions.*.topic.required' => 'Question topic is required.',
            'sections.*.questions.*.prompt.required' => 'Question prompt is required.',
            'sections.*.questions.*.response_time_seconds.required' => 'Response time is required for each question.',
            'sections.*.questions.*.response_time_seconds.min' => 'Minimum response time is 30 seconds.',
            'sections.*.questions.*.response_time_seconds.max' => 'Maximum response time is 5 minutes (300 seconds).'
        ];
    }
}