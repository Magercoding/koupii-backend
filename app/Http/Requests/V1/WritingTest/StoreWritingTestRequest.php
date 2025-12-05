<?php

namespace App\Http\Requests\V1\WritingTest;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreWritingTestRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'test_type' => 'required|in:academic,general',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'timer_mode' => 'nullable|in:test,prompt',
            'timer_settings' => 'nullable|array',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|array',

            // Writing prompts
            'writing_prompts' => 'required|array|min:1',
            'writing_prompts.*.title' => 'required|string|max:255',
            'writing_prompts.*.prompt_text' => 'required|string',
            'writing_prompts.*.prompt_type' => 'required|in:essay,letter,report,review,article,proposal',
            'writing_prompts.*.word_limit' => 'nullable|integer|min:50|max:1000',
            'writing_prompts.*.time_limit' => 'nullable|integer|min:10|max:120', // minutes
            'writing_prompts.*.instructions' => 'nullable|string',
            'writing_prompts.*.sample_answer' => 'nullable|string',

            // Writing criteria
            'writing_prompts.*.criteria' => 'required|array|min:1',
            'writing_prompts.*.criteria.*.name' => 'required|string|max:100',
            'writing_prompts.*.criteria.*.description' => 'required|string',
            'writing_prompts.*.criteria.*.max_score' => 'required|integer|min:1|max:10',
            'writing_prompts.*.criteria.*.weight' => 'required|numeric|min:0|max:1',
            'writing_prompts.*.criteria.*.rubric' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'writing_prompts.required' => 'At least one writing prompt is required.',
            'writing_prompts.*.criteria.required' => 'Each writing prompt must have evaluation criteria.',
            'writing_prompts.*.criteria.*.weight.max' => 'Criteria weight cannot exceed 1.0.',
            'writing_prompts.*.word_limit.min' => 'Word limit must be at least 50 words.',
            'writing_prompts.*.time_limit.min' => 'Time limit must be at least 10 minutes.',
        ];
    }
}
