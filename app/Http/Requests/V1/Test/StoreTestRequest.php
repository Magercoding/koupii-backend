<?php

namespace App\Http\Requests\V1\Test;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreTestRequest extends BaseRequest
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
            'type' => ['required', Rule::in(['reading', 'listening', 'speaking', 'writing'])],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'test_type' => ['nullable', Rule::in(['single', 'final'])],
            'timer_mode' => ['nullable', Rule::in(['countdown', 'countup', 'none'])],
            'timer_settings' => 'nullable|array',
            'timer_settings.time_limit' => 'nullable|integer|min:60',
            'timer_settings.warning_time' => 'nullable|integer|min:0',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:0|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|array',
            'settings.shuffle_questions' => 'nullable|boolean',
            'settings.shuffle_options' => 'nullable|boolean',
            'settings.show_results' => 'nullable|boolean',
            
            // Passages validation
            'passages' => 'nullable|array',
            'passages.*.title' => 'nullable|string|max:255',
            'passages.*.description' => 'nullable|string',
            'passages.*.audio_file_path' => 'nullable|string',
            'passages.*.transcript_type' => ['nullable', Rule::in(['descriptive', 'conversation'])],
            'passages.*.transcript' => 'nullable|json',
            
            // Question groups validation
            'passages.*.question_groups' => 'nullable|array',
            'passages.*.question_groups.*.instruction' => 'nullable|string',
            
            // Questions validation
            'passages.*.question_groups.*.questions' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.question_type' => 'required|string',
            'passages.*.question_groups.*.questions.*.question_number' => 'nullable|numeric',
            'passages.*.question_groups.*.questions.*.question_text' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.question_data' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.correct_answers' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.correct_answers.*' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.points_value' => 'nullable|numeric|min:0',
            
            // Question options validation
            'passages.*.question_groups.*.questions.*.options' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.options.*.option_key' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.options.*.option_text' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'test title',
            'description' => 'test description',
            'type' => 'test type',
            'difficulty' => 'difficulty level',
            'test_type' => 'test type',
            'timer_mode' => 'timer mode',
            'timer_settings' => 'timer settings',
            'allow_repetition' => 'allow repetition',
            'max_repetition_count' => 'maximum repetition count',
            'is_public' => 'public status',
            'is_published' => 'publication status',
            'settings' => 'test settings',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Test type must be one of: reading, listening, speaking, writing',
            'difficulty.in' => 'Difficulty must be one of: beginner, intermediate, advanced',
            'test_type.in' => 'Test type must be one of: single, final',
            'timer_mode.in' => 'Timer mode must be one of: countdown, countup, none',
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 10',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_repetition' => $this->boolean('allow_repetition'),
            'is_public' => $this->boolean('is_public'),
            'is_published' => $this->boolean('is_published'),
        ]);

        // Only parse JSON strings if they are actually strings (for multipart/form-data backward compatibility)
        if ($this->has('timer_settings') && is_string($this->input('timer_settings'))) {
            $this->merge([
                'timer_settings' => json_decode($this->input('timer_settings'), true)
            ]);
        }

        if ($this->has('settings') && is_string($this->input('settings'))) {
            $this->merge([
                'settings' => json_decode($this->input('settings'), true)
            ]);
        }

        if ($this->has('passages') && is_string($this->input('passages'))) {
            $this->merge([
                'passages' => json_decode($this->input('passages'), true)
            ]);
        }
    }
}