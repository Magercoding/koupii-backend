<?php

namespace App\Http\Requests\V1\ListeningTest;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListeningTestRequest extends BaseRequest
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
            'settings.audio_format' => 'nullable|string',
            'settings.audio_speed' => 'nullable|numeric|min:0.5|max:2.0',
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
            'title.max' => 'Test title cannot exceed 255 characters',
            'description.required' => 'Test description is required',
            'test_type.required' => 'Test type is required',
            'test_type.in' => 'Test type must be one of: academic, general, business, ielts, toefl',
            'difficulty.required' => 'Difficulty level is required',
            'difficulty.in' => 'Difficulty must be one of: beginner, intermediate, advanced',
            'timer_mode.required' => 'Timer mode is required',
            'timer_mode.in' => 'Timer mode must be one of: none, test, practice',
            'timer_settings.test_time.integer' => 'Test time must be a number',
            'timer_settings.test_time.min' => 'Test time must be at least 1 minute',
            'timer_settings.test_time.max' => 'Test time cannot exceed 480 minutes (8 hours)',
            'timer_settings.warning_time.integer' => 'Warning time must be a number',
            'timer_settings.warning_time.min' => 'Warning time must be at least 1 minute',
            'max_repetition_count.integer' => 'Maximum repetition count must be a number',
            'max_repetition_count.min' => 'Maximum repetition count must be at least 1',
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 10',
            'settings.audio_speed.numeric' => 'Audio speed must be a number',
            'settings.audio_speed.min' => 'Audio speed cannot be less than 0.5',
            'settings.audio_speed.max' => 'Audio speed cannot exceed 2.0',
            'settings.tags.array' => 'Tags must be an array',
            'settings.tags.*.string' => 'Each tag must be a string',
            'settings.tags.*.max' => 'Each tag cannot exceed 50 characters',
        ];
    }
}