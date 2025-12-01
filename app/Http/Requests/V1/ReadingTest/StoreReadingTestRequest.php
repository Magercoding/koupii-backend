<?php

namespace App\Http\Requests\V1\ReadingTest;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingTestRequest extends FormRequest
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
            'description' => 'nullable|string',
            'type' => 'required|in:reading,listening,speaking,writing',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'test_type' => 'required|in:single,final',
            'timer_mode' => 'nullable|in:countdown,countup,none',
            'timer_settings' => 'nullable|array',
            'timer_settings.hours' => 'nullable|integer|min:0',
            'timer_settings.minutes' => 'nullable|integer|min:0|max:59',
            'timer_settings.seconds' => 'nullable|integer|min:0|max:59',
            'allow_repetition' => 'nullable|boolean',
            'max_repetition_count' => 'nullable|integer|min:1',
            'is_public' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'settings' => 'nullable|array',

            'passages' => 'required|array|min:1',
            'passages.*.title' => 'nullable|string|max:255',
            'passages.*.description' => 'nullable|string',

            'passages.*.question_groups' => 'required|array|min:1',
            'passages.*.question_groups.*.instruction' => 'nullable|string',

            'passages.*.question_groups.*.questions' => 'required|array|min:1',
            'passages.*.question_groups.*.questions.*.question_type' => 'required|string',
            'passages.*.question_groups.*.questions.*.question_number' => 'nullable|numeric|min:1',
            'passages.*.question_groups.*.questions.*.question_text' => 'nullable|string',

            'passages.*.question_groups.*.questions.*.question_data' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.question_data.images.*' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',

            'passages.*.question_groups.*.questions.*.correct_answers' => 'nullable|array',

            'passages.*.question_groups.*.questions.*.options' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.options.*.option_key' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.options.*.option_text' => 'nullable|string',

            'passages.*.question_groups.*.questions.*.breakdown' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.breakdown.explanation' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.breakdown.highlights' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.breakdown.highlights.*.start_char_index' => 'nullable|integer|min:0',
            'passages.*.question_groups.*.questions.*.breakdown.highlights.*.end_char_index' => 'nullable|integer|min:0',

            'passages.*.question_groups.*.questions.*.items' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.items.*.question_number' => 'nullable|numeric|min:0.1',
            'passages.*.question_groups.*.questions.*.items.*.correct_answers' => 'nullable|array',
        ];
    
    }
}
