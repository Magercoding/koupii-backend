<?php

namespace App\Http\Requests\V1\WritingTask;

use App\Http\Requests\BaseRequest;
use App\Models\WritingTaskQuestion;
use Illuminate\Validation\Rule;

class StoreWritingTaskQuestionRequest extends BaseRequest
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
     */
    public function rules(): array
    {
        return [
            'question_type' => ['required', 'string', Rule::in(array_keys(WritingTaskQuestion::QUESTION_TYPES))],
            'question_text' => 'required|string|max:2000',
            'instructions' => 'nullable|string|max:1000',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'min_word_count' => 'nullable|integer|min:10|max:4000',
            'time_limit_seconds' => 'nullable|integer|min:60|max:7200',
            'difficulty_level' => ['nullable', Rule::in(WritingTaskQuestion::DIFFICULTY_LEVELS)],
            'points' => 'nullable|numeric|min:0|max:100',
            'rubric' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'question_data' => 'nullable|array',
            'is_required' => 'boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'question_type.required' => 'Question type is required',
            'question_type.in' => 'Invalid question type selected',
            'question_text.required' => 'Question text is required',
            'question_text.max' => 'Question text cannot exceed 2000 characters',
            'word_limit.min' => 'Word limit must be at least 50 words',
            'word_limit.max' => 'Word limit cannot exceed 5000 words',
            'min_word_count.min' => 'Minimum word count must be at least 10 words',
            'min_word_count.max' => 'Minimum word count cannot exceed 4000 words',
            'time_limit_seconds.min' => 'Time limit must be at least 60 seconds',
            'time_limit_seconds.max' => 'Time limit cannot exceed 7200 seconds (2 hours)',
            'difficulty_level.in' => 'Invalid difficulty level selected',
            'points.min' => 'Points cannot be negative',
            'points.max' => 'Points cannot exceed 100',
        ];
    }
}