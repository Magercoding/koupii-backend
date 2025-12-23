<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_id' => [
                'required',
                'uuid',
                'exists:speaking_submissions,id'
            ],
            'overall_score' => [
                'required',
                'numeric',
                'min:0',
                'max:10'
            ],
            'pronunciation_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:10'
            ],
            'fluency_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:10'
            ],
            'grammar_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:10'
            ],
            'vocabulary_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:10'
            ],
            'content_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:10'
            ],
            'feedback' => [
                'required',
                'string',
                'min:10',
                'max:2000'
            ],
            'detailed_comments' => [
                'sometimes',
                'string',
                'max:5000'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_id.required' => 'Submission ID is required.',
            'submission_id.uuid' => 'Submission ID must be a valid UUID.',
            'submission_id.exists' => 'The specified submission does not exist.',
            
            'overall_score.required' => 'Overall score is required.',
            'overall_score.numeric' => 'Overall score must be a number.',
            'overall_score.min' => 'Overall score must be at least 0.',
            'overall_score.max' => 'Overall score must not exceed 10.',
            
            'pronunciation_score.numeric' => 'Pronunciation score must be a number.',
            'pronunciation_score.min' => 'Pronunciation score must be at least 0.',
            'pronunciation_score.max' => 'Pronunciation score must not exceed 10.',
            
            'fluency_score.numeric' => 'Fluency score must be a number.',
            'fluency_score.min' => 'Fluency score must be at least 0.',
            'fluency_score.max' => 'Fluency score must not exceed 10.',
            
            'grammar_score.numeric' => 'Grammar score must be a number.',
            'grammar_score.min' => 'Grammar score must be at least 0.',
            'grammar_score.max' => 'Grammar score must not exceed 10.',
            
            'vocabulary_score.numeric' => 'Vocabulary score must be a number.',
            'vocabulary_score.min' => 'Vocabulary score must be at least 0.',
            'vocabulary_score.max' => 'Vocabulary score must not exceed 10.',
            
            'content_score.numeric' => 'Content score must be a number.',
            'content_score.min' => 'Content score must be at least 0.',
            'content_score.max' => 'Content score must not exceed 10.',
            
            'feedback.required' => 'Feedback is required.',
            'feedback.string' => 'Feedback must be a string.',
            'feedback.min' => 'Feedback must be at least 10 characters.',
            'feedback.max' => 'Feedback must not exceed 2000 characters.',
            
            'detailed_comments.string' => 'Detailed comments must be a string.',
            'detailed_comments.max' => 'Detailed comments must not exceed 5000 characters.',
        ];
    }
}