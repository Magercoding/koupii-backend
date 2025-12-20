<?php

namespace App\Http\Requests\V1\ReadingTest;

use App\Http\Requests\BaseRequest;

class SubmitTestRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'time_taken_seconds' => 'nullable|integer|min:0',
            'answers' => 'sometimes|array',
            'answers.*.question_id' => 'required_with:answers|exists:test_questions,id',
            'answers.*.answer' => 'required_with:answers',
            'answers.*.time_spent_seconds' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'time_taken_seconds.integer' => 'Time taken must be a valid number',
            'time_taken_seconds.min' => 'Time taken cannot be negative',
            'answers.array' => 'Answers must be an array',
            'answers.*.question_id.required_with' => 'Question ID is required for each answer',
            'answers.*.question_id.exists' => 'Invalid question ID in answers',
            'answers.*.answer.required_with' => 'Answer is required for each question',
            'answers.*.time_spent_seconds.integer' => 'Time spent must be a valid number',
            'answers.*.time_spent_seconds.min' => 'Time spent cannot be negative',
        ];
    }
}