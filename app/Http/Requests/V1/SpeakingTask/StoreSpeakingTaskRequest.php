<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class StoreSpeakingTaskRequest extends BaseRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string|max:1000',
            'instructions'           => 'nullable|string|max:2000',
            'difficulty'             => 'required|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'is_published'           => 'boolean',
            'class_id'               => 'nullable|string|exists:classes,id',
            'due_date'               => 'nullable|date|after:now',
            'assign_on_create'       => 'nullable|boolean',
            'timer_mode'             => 'nullable|string|in:countdown,countup,none',
            'timer_settings'         => 'nullable|array',
            'timer_settings.hours'   => 'nullable|integer|min:0|max:23',
            'timer_settings.minutes' => 'nullable|integer|min:0|max:59',
            'timer_settings.seconds' => 'nullable|integer|min:0|max:59',
            
            // Speaking sections
            'sections' => 'required|array|min:1|max:10',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.instructions' => 'nullable|string|max:1000',
            'sections.*.order_index' => 'required|integer|min:0',
            'sections.*.time_limit_seconds' => 'nullable|integer|min:30|max:1800',
            
            // Speaking questions
            'sections.*.questions' => 'required|array|min:1|max:20',
            'sections.*.questions.*.topic' => 'nullable|string|max:255',
            'sections.*.questions.*.prompt' => 'required|string|max:2000',
            'sections.*.questions.*.preparation_time_seconds' => 'nullable|integer|min:0|max:300',
            'sections.*.questions.*.response_time_seconds' => 'nullable|integer|min:0|max:300',
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
            'title.required'                              => 'The speaking task title is required.',
            'title.max'                                   => 'The title may not be greater than 255 characters.',
            'difficulty.required'                         => 'The difficulty level is required.',
            'difficulty.in'                               => 'The difficulty must be one of: beginner, elementary, intermediate, upper_intermediate, advanced, proficiency.',
            'timer_mode.in'                               => 'Timer mode must be one of: countdown, countup, none.',
            'sections.required'                           => 'At least one speaking section is required.',
            'sections.min'                                => 'At least one speaking section is required.',
            'sections.max'                                => 'Maximum 10 sections allowed per speaking task.',
            'sections.*.questions.required'               => 'At least one question is required per section.',
            'sections.*.questions.min'                    => 'At least one question is required per section.',
            'sections.*.questions.max'                    => 'Maximum 20 questions allowed per section.',
            'sections.*.questions.*.prompt.required'      => 'Question prompt is required.',
            'sections.*.questions.*.response_time_seconds.required' => 'Response time is required for each question.',
            'sections.*.questions.*.response_time_seconds.min'      => 'Response time must be at least 30 seconds.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Casts boolean fields sent as strings from multipart/form-data,
     * and normalises timer_settings when sent as a JSON string.
     */
    protected function prepareForValidation(): void
    {
        $merge = [
            'is_published'     => $this->boolean('is_published'),
            'assign_on_create' => $this->boolean('assign_on_create'),
        ];

        // timer_settings may arrive as a JSON string (e.g. from non-FormData clients)
        if ($this->has('timer_settings') && is_string($this->input('timer_settings'))) {
            $decoded = json_decode($this->input('timer_settings'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['timer_settings'] = $decoded;
            }
        }

        $this->merge($merge);
    }
}
