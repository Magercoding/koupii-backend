<?php

namespace App\Http\Requests\V1\Test;

use App\Http\Requests\BaseRequest;

class StoreTestSubmissionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['student', 'admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|uuid|exists:test_questions,id',
            'answers.*.answer' => 'required',
            'answers.*.time_taken' => 'nullable|integer|min:0',
            'total_time_taken' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'answers' => 'test answers',
            'answers.*.question_id' => 'question ID',
            'answers.*.answer' => 'answer',
            'answers.*.time_taken' => 'time taken for question',
            'total_time_taken' => 'total time taken',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'At least one answer is required',
            'answers.*.question_id.required' => 'Question ID is required for each answer',
            'answers.*.question_id.exists' => 'The selected question does not exist',
            'answers.*.answer.required' => 'Answer is required for each question',
        ];
    }
}