<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSpeakingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the submission or is authorized to submit
        $submission = $this->route('submission');
        return $submission && auth()->id() === $submission->student_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'force_submit' => [
                'sometimes',
                'boolean'
            ],
            'time_spent' => [
                'sometimes',
                'integer',
                'min:0',
                'max:7200' // 2 hours max
            ],
            'completion_notes' => [
                'sometimes',
                'string',
                'max:1000'
            ],
            'self_assessment' => [
                'sometimes',
                'array'
            ],
            'self_assessment.difficulty_rating' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5'
            ],
            'self_assessment.confidence_rating' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5'
            ],
            'self_assessment.effort_rating' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5'
            ],
            'self_assessment.comments' => [
                'sometimes',
                'string',
                'max:500'
            ],
            'technical_issues' => [
                'sometimes',
                'array'
            ],
            'technical_issues.had_issues' => [
                'sometimes',
                'boolean'
            ],
            'technical_issues.issue_description' => [
                'sometimes',
                'string',
                'max:1000'
            ],
            'technical_issues.audio_quality' => [
                'sometimes',
                'string',
                'in:excellent,good,fair,poor'
            ],
            'final_review' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'force_submit' => 'force submit',
            'time_spent' => 'time spent',
            'completion_notes' => 'completion notes',
            'self_assessment.difficulty_rating' => 'difficulty rating',
            'self_assessment.confidence_rating' => 'confidence rating',
            'self_assessment.effort_rating' => 'effort rating',
            'self_assessment.comments' => 'self-assessment comments',
            'technical_issues.had_issues' => 'technical issues flag',
            'technical_issues.issue_description' => 'issue description',
            'technical_issues.audio_quality' => 'audio quality rating',
            'final_review' => 'final review'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'time_spent.min' => 'Time spent cannot be negative.',
            'time_spent.max' => 'Time spent cannot exceed 2 hours.',
            'completion_notes.max' => 'Completion notes cannot exceed 1000 characters.',
            
            'self_assessment.difficulty_rating.min' => 'Difficulty rating must be between 1 and 5.',
            'self_assessment.difficulty_rating.max' => 'Difficulty rating must be between 1 and 5.',
            'self_assessment.confidence_rating.min' => 'Confidence rating must be between 1 and 5.',
            'self_assessment.confidence_rating.max' => 'Confidence rating must be between 1 and 5.',
            'self_assessment.effort_rating.min' => 'Effort rating must be between 1 and 5.',
            'self_assessment.effort_rating.max' => 'Effort rating must be between 1 and 5.',
            'self_assessment.comments.max' => 'Self-assessment comments cannot exceed 500 characters.',
            
            'technical_issues.issue_description.max' => 'Issue description cannot exceed 1000 characters.',
            'technical_issues.audio_quality.in' => 'Audio quality rating must be excellent, good, fair, or poor.',
        ];
    }
}