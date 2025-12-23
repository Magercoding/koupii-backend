<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSpeakingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authorized to review speaking submissions
        return $this->user()->can('review', $this->route('submission'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'total_score' => [
                'required',
                'numeric',
                'min:0',
                'max:100'
            ],
            'overall_feedback' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'grading_rubric' => [
                'sometimes',
                'array'
            ],
            'grading_rubric.fluency' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'grading_rubric.pronunciation' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'grading_rubric.vocabulary' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'grading_rubric.grammar' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'question_scores' => [
                'sometimes',
                'array'
            ],
            'question_scores.*.question_id' => [
                'required_with:question_scores',
                'uuid',
                'exists:speaking_questions,id'
            ],
            'question_scores.*.score' => [
                'required_with:question_scores',
                'numeric',
                'min:0',
                'max:100'
            ],
            'question_scores.*.comment' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'question_scores.*.rubric_scores' => [
                'sometimes',
                'array'
            ],
            'question_scores.*.rubric_scores.fluency' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'question_scores.*.rubric_scores.pronunciation' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'question_scores.*.rubric_scores.vocabulary' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'question_scores.*.rubric_scores.grammar' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:25'
            ],
            'review_status' => [
                'sometimes',
                'string',
                'in:draft,completed,needs_revision'
            ],
            'review_notes' => [
                'sometimes',
                'string',
                'max:1000'
            ],
            'time_spent_reviewing' => [
                'sometimes',
                'integer',
                'min:0'
            ],
            'recommendations' => [
                'sometimes',
                'array'
            ],
            'recommendations.strengths' => [
                'sometimes',
                'array'
            ],
            'recommendations.areas_for_improvement' => [
                'sometimes',
                'array'
            ],
            'recommendations.next_steps' => [
                'sometimes',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'total_score' => 'total score',
            'overall_feedback' => 'overall feedback',
            'grading_rubric.fluency' => 'fluency score',
            'grading_rubric.pronunciation' => 'pronunciation score',
            'grading_rubric.vocabulary' => 'vocabulary score',
            'grading_rubric.grammar' => 'grammar score',
            'question_scores.*.question_id' => 'question ID',
            'question_scores.*.score' => 'question score',
            'question_scores.*.comment' => 'question comment',
            'review_status' => 'review status',
            'review_notes' => 'review notes',
            'time_spent_reviewing' => 'time spent reviewing',
            'recommendations.next_steps' => 'next steps recommendation'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'total_score.required' => 'Total score is required.',
            'total_score.numeric' => 'Total score must be a number.',
            'total_score.min' => 'Total score cannot be negative.',
            'total_score.max' => 'Total score cannot exceed 100.',
            
            'overall_feedback.max' => 'Overall feedback cannot exceed 2000 characters.',
            
            'grading_rubric.fluency.min' => 'Fluency score cannot be negative.',
            'grading_rubric.fluency.max' => 'Fluency score cannot exceed 25.',
            'grading_rubric.pronunciation.min' => 'Pronunciation score cannot be negative.',
            'grading_rubric.pronunciation.max' => 'Pronunciation score cannot exceed 25.',
            'grading_rubric.vocabulary.min' => 'Vocabulary score cannot be negative.',
            'grading_rubric.vocabulary.max' => 'Vocabulary score cannot exceed 25.',
            'grading_rubric.grammar.min' => 'Grammar score cannot be negative.',
            'grading_rubric.grammar.max' => 'Grammar score cannot exceed 25.',
            
            'question_scores.*.question_id.required_with' => 'Question ID is required when providing question scores.',
            'question_scores.*.question_id.exists' => 'The specified question does not exist.',
            'question_scores.*.score.required_with' => 'Question score is required when providing question scores.',
            'question_scores.*.score.min' => 'Question score cannot be negative.',
            'question_scores.*.score.max' => 'Question score cannot exceed 100.',
            'question_scores.*.comment.max' => 'Question comment cannot exceed 1000 characters.',
            
            'review_status.in' => 'Review status must be draft, completed, or needs_revision.',
            'review_notes.max' => 'Review notes cannot exceed 1000 characters.',
            'time_spent_reviewing.min' => 'Time spent reviewing cannot be negative.',
            'recommendations.next_steps.max' => 'Next steps recommendation cannot exceed 1000 characters.',
        ];
    }
}