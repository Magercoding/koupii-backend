<?php

namespace App\Http\Requests\V1\WritingTask;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class ReviewSubmissionRequest extends BaseRequest
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
            'score' => 'nullable|integer|min:0|max:100',
            'comments' => 'nullable|string',
            'feedback_json' => 'nullable|array',
            'feedback_json.strengths' => 'nullable|array',
            'feedback_json.strengths.*' => 'string|max:500',
            'feedback_json.areas_for_improvement' => 'nullable|array',
            'feedback_json.areas_for_improvement.*' => 'string|max:500',
            'feedback_json.grammar_errors' => 'nullable|array',
            'feedback_json.grammar_errors.*' => 'array',
            'feedback_json.grammar_errors.*.text' => 'required_with:feedback_json.grammar_errors.*|string',
            'feedback_json.grammar_errors.*.suggestion' => 'required_with:feedback_json.grammar_errors.*|string',
            'feedback_json.grammar_errors.*.position' => 'nullable|integer',
            'feedback_json.vocabulary_suggestions' => 'nullable|array',
            'feedback_json.vocabulary_suggestions.*' => 'array',
            'feedback_json.vocabulary_suggestions.*.original' => 'required_with:feedback_json.vocabulary_suggestions.*|string',
            'feedback_json.vocabulary_suggestions.*.suggestion' => 'required_with:feedback_json.vocabulary_suggestions.*|string',
            'feedback_json.structure_feedback' => 'nullable|string',
            'feedback_json.overall_comment' => 'nullable|string',
        ];
    }


    public function messages(): array
    {
        return [
            'score.min' => 'Score cannot be negative',
            'score.max' => 'Score cannot exceed 100',
            'feedback_json.strengths.*.max' => 'Each strength comment cannot exceed 500 characters',
            'feedback_json.areas_for_improvement.*.max' => 'Each improvement area cannot exceed 500 characters',
            'feedback_json.grammar_errors.*.text.required_with' => 'Grammar error text is required',
            'feedback_json.grammar_errors.*.suggestion.required_with' => 'Grammar error suggestion is required',
            'feedback_json.vocabulary_suggestions.*.original.required_with' => 'Original word is required for vocabulary suggestions',
            'feedback_json.vocabulary_suggestions.*.suggestion.required_with' => 'Suggested word is required for vocabulary suggestions',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            
            if (!$this->score && !$this->comments && !$this->feedback_json) {
                $validator->errors()->add('review', 'At least one of score, comments, or detailed feedback must be provided.');
            }
        });
    }
}
