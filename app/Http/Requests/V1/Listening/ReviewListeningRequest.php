<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class ReviewListeningRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
            'private_notes' => 'nullable|string|max:1000',
            'status' => 'required|in:reviewed,needs_revision,approved',
            'detailed_feedback' => 'nullable|array',
            'detailed_feedback.*.question_id' => 'required|exists:listening_questions,id',
            'detailed_feedback.*.feedback' => 'nullable|string|max:500',
            'detailed_feedback.*.points_awarded' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'score' => 'review score',
            'feedback' => 'feedback',
            'private_notes' => 'private notes',
            'status' => 'review status',
            'detailed_feedback' => 'detailed feedback',
            'detailed_feedback.*.question_id' => 'question ID',
            'detailed_feedback.*.feedback' => 'question feedback',
            'detailed_feedback.*.points_awarded' => 'points awarded',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'score.max' => 'Score cannot exceed 100',
            'score.min' => 'Score cannot be negative',
            'status.in' => 'Status must be one of: reviewed, needs_revision, approved',
            'detailed_feedback.*.question_id.required' => 'Each detailed feedback must have a valid question ID',
            'detailed_feedback.*.question_id.exists' => 'The question ID does not exist',
            'detailed_feedback.*.points_awarded.min' => 'Points awarded cannot be negative',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reviewer_id' => $this->user()->id,
            'reviewed_at' => now(),
        ]);
    }
}