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
            'difficulty'             => 'required|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'is_published'           => 'boolean',
            'class_id'               => 'nullable|string|exists:classes,id',
            'due_date'               => 'nullable|date|after:now',
            'assign_on_create'       => 'nullable|boolean',
            'timer_mode'             => 'nullable|in:countdown,countup,none',
            'timer_settings'         => 'nullable|array',
            'timer_settings.hours'   => 'nullable|integer|min:0|max:23',
            'timer_settings.minutes' => 'nullable|integer|min:0|max:59',
            'timer_settings.seconds' => 'nullable|integer|min:0|max:59',

            // Passages structure
            'passages'                                        => 'required|array|min:1',
            'passages.*.title'                               => 'required|string|max:255',
            'passages.*.description'                         => 'nullable|string|max:2000',
            'passages.*.image_context'                       => 'nullable|file|mimes:jpeg,png,webp|max:5120',
            'passages.*.questions'                           => 'required|array|min:1',
            'passages.*.questions.*.question_text'           => 'required|string',
            'passages.*.questions.*.voice_limit'             => 'required|integer|min:1',
            'passages.*.questions.*.question_number'         => 'nullable|integer',
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
            'passages.required'                           => 'At least one passage is required.',
            'passages.min'                                => 'At least one passage is required.',
            'passages.*.title.required'                   => 'Passage title is required.',
            'passages.*.questions.required'               => 'At least one question is required per passage.',
            'passages.*.questions.min'                    => 'At least one question is required per passage.',
            'passages.*.questions.*.question_text.required' => 'Question text is required.',
            'passages.*.questions.*.voice_limit.required' => 'Voice limit is required for each question.',
            'passages.*.questions.*.voice_limit.min'      => 'Voice limit must be at least 1 second.',
            'passages.*.image_context.mimes'              => 'Passage image must be jpeg, png, or webp.',
            'passages.*.image_context.max'                => 'Passage image cannot exceed 5 MB.',
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
