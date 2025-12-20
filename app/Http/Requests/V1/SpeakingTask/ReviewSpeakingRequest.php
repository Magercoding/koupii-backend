<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class ReviewSpeakingRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'total_score' => 'required|integer|min:0|max:100',
            'overall_feedback' => 'nullable|string',
            'question_scores' => 'nullable|array',
            'question_scores.*.question_id' => 'required_with:question_scores|exists:speaking_questions,id',
            'question_scores.*.score' => 'required_with:question_scores|integer|min:0|max:100',
            'question_scores.*.comment' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'total_score.required' => 'Total score is required',
            'total_score.integer' => 'Total score must be a number',
            'total_score.min' => 'Total score cannot be negative',
            'total_score.max' => 'Total score cannot exceed 100',
        ];
    }
}