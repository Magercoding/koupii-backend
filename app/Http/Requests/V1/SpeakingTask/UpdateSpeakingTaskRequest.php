<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class UpdateSpeakingTaskRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
            'allow_repetition' => 'sometimes|boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'timer_type' => 'sometimes|in:countdown,countup,none',
            'time_limit_seconds' => 'nullable|integer|min:1',

            'sections' => 'sometimes|array|min:1',
            'sections.*.section_type' => 'required_with:sections|in:introduction,long_turn,discussion',
            'sections.*.description' => 'nullable|string',
            'sections.*.prep_time_seconds' => 'nullable|integer|min:1',

            'sections.*.topics' => 'required_with:sections|array|min:1',
            'sections.*.topics.*.topic_name' => 'required_with:sections.*.topics|string|max:255',

            'sections.*.topics.*.questions' => 'required_with:sections.*.topics|array|min:1',
            'sections.*.topics.*.questions.*.question_number' => 'required_with:sections.*.topics.*.questions|integer|min:1',
            'sections.*.topics.*.questions.*.question_text' => 'required_with:sections.*.topics.*.questions|string',
            'sections.*.topics.*.questions.*.time_limit_seconds' => 'required_with:sections.*.topics.*.questions|integer|min:1',
        ];
    }
}