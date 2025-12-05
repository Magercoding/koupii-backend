<?php

namespace App\Http\Requests\V1\WritingTest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWritingTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        $test = $this->route('id') ? \App\Models\Test::find($this->route('id')) : null;

        return auth()->check() && (
            auth()->user()->role === 'admin' ||
            ($test && $test->creator_id === auth()->id())
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'test_type' => 'sometimes|required|in:academic,general',
            'difficulty' => 'sometimes|required|in:beginner,intermediate,advanced',
            'timer_mode' => 'nullable|in:test,prompt',
            'timer_settings' => 'nullable|array',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|array',

            // Writing prompts (optional for updates)
            'writing_prompts' => 'sometimes|array|min:1',
            'writing_prompts.*.id' => 'nullable|exists:writing_prompts,id',
            'writing_prompts.*.title' => 'required|string|max:255',
            'writing_prompts.*.prompt_text' => 'required|string',
            'writing_prompts.*.prompt_type' => 'required|in:essay,letter,report,review,article,proposal',
            'writing_prompts.*.word_limit' => 'nullable|integer|min:50|max:1000',
            'writing_prompts.*.time_limit' => 'nullable|integer|min:10|max:120',
            'writing_prompts.*.instructions' => 'nullable|string',
            'writing_prompts.*.sample_answer' => 'nullable|string',

            // Writing criteria
            'writing_prompts.*.criteria' => 'sometimes|array|min:1',
            'writing_prompts.*.criteria.*.id' => 'nullable|exists:writing_criteria,id',
            'writing_prompts.*.criteria.*.name' => 'required|string|max:100',
            'writing_prompts.*.criteria.*.description' => 'required|string',
            'writing_prompts.*.criteria.*.max_score' => 'required|integer|min:1|max:10',
            'writing_prompts.*.criteria.*.weight' => 'required|numeric|min:0|max:1',
            'writing_prompts.*.criteria.*.rubric' => 'nullable|array',
        ];
}
}
