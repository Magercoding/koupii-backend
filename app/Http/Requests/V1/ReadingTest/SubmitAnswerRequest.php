<?php

namespace App\Http\Requests\V1\ReadingTest;

use App\Http\Requests\BaseRequest;

class SubmitAnswerRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'question_id' => 'required|exists:test_questions,id',
            'answer' => 'required',
            'time_spent_seconds' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required',
            'question_id.exists' => 'Invalid question ID',
            'answer.required' => 'Answer is required',
            'time_spent_seconds.integer' => 'Time spent must be a valid number',
            'time_spent_seconds.min' => 'Time spent cannot be negative',
        ];
    }

    public function prepareForValidation(): void
    {
        // Handle different answer formats based on question type
        $answer = $this->input('answer');
        
        // Convert answer to appropriate format if needed
        if (is_string($answer) && in_array($answer, ['true', 'false', 'not given', 'yes', 'no'])) {
            $this->merge(['answer' => strtolower($answer)]);
        }
    }
}