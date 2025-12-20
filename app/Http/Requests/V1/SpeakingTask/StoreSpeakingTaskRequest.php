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
            'instructions' => 'nullable|string',
            'difficulty' => 'in:beginner,intermediate,advanced',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'timer_type' => 'in:countdown,countup,none',
            'time_limit_seconds' => 'nullable|integer|min:1',

            'sections' => 'required|array|min:1',
            'sections.*.section_type' => 'required|in:introduction,long_turn,discussion',
            'sections.*.description' => 'nullable|string',
            'sections.*.prep_time_seconds' => 'nullable|integer|min:1',

            'sections.*.topics' => 'required|array|min:1',
            'sections.*.topics.*.topic_name' => 'required|string|max:255',

            'sections.*.topics.*.questions' => 'required|array|min:1',
            'sections.*.topics.*.questions.*.question_number' => 'required|integer|min:1',
            'sections.*.topics.*.questions.*.question_text' => 'required|string',
            'sections.*.topics.*.questions.*.time_limit_seconds' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'sections.required' => 'At least one section is required',
            'sections.*.topics.required' => 'At least one topic is required for each section',
            'sections.*.topics.*.questions.required' => 'At least one question is required for each topic',
        ];
    }
}
