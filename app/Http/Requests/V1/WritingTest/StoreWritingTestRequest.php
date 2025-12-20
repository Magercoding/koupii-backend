<?php

namespace App\Http\Requests\V1\WritingTest;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWritingTestRequest extends BaseRequest
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
            'test_type' => ['required', Rule::in(['academic', 'general', 'business', 'ielts', 'toefl'])],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'timer_mode' => ['required', Rule::in(['none', 'test', 'practice'])],
            'timer_settings' => 'nullable|array',
            'timer_settings.test_time' => 'nullable|integer|min:1|max:480', // max 8 hours
            'timer_settings.warning_time' => 'nullable|integer|min:1',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|array',
            'settings.instructions' => 'nullable|string',
            'settings.sample_format' => 'nullable|string',
            'settings.word_limit' => 'nullable|integer|min:50|max:5000',
            'settings.cover_image' => 'nullable|string',
            'settings.tags' => 'nullable|array',
            'settings.tags.*' => 'string|max:50',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Test title is required',
            'description.required' => 'Test description is required',
            'test_type.required' => 'Test type is required',
            'test_type.in' => 'Invalid test type selected',
            'difficulty.required' => 'Difficulty level is required',
            'difficulty.in' => 'Invalid difficulty level selected',
            'timer_mode.required' => 'Timer mode is required',
            'timer_mode.in' => 'Invalid timer mode selected',
            'timer_settings.test_time.max' => 'Test time cannot exceed 8 hours',
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 10',
            'settings.word_limit.min' => 'Word limit must be at least 50 words',
            'settings.word_limit.max' => 'Word limit cannot exceed 5000 words',
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
    }
}
