<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpeakingQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can update the speaking question
        return $this->user()->can('update', $this->route('speakingQuestion'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'question_text' => [
                'sometimes',
                'string',
                'max:1000'
            ],
            'question_type' => [
                'sometimes',
                'string',
                'in:describe,narrate,opinion,compare,analyze,present,debate'
            ],
            'instruction' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000'
            ],
            'preparation_time' => [
                'sometimes',
                'integer',
                'min:0',
                'max:300' // 5 minutes max
            ],
            'response_time' => [
                'sometimes',
                'integer',
                'min:30',
                'max:600' // 10 minutes max
            ],
            'difficulty_level' => [
                'sometimes',
                'string',
                'in:beginner,intermediate,advanced'
            ],
            'max_score' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100'
            ],
            'order_index' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'keywords' => [
                'sometimes',
                'array'
            ],
            'keywords.*' => [
                'string',
                'max:50'
            ],
            'sample_response' => [
                'sometimes',
                'nullable',
                'string',
                'max:3000'
            ],
            'evaluation_criteria' => [
                'sometimes',
                'array'
            ],
            'evaluation_criteria.fluency' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'evaluation_criteria.pronunciation' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'evaluation_criteria.vocabulary' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'evaluation_criteria.grammar' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'prompts' => [
                'sometimes',
                'array'
            ],
            'prompts.*' => [
                'string',
                'max:255'
            ],
            'context_information' => [
                'sometimes',
                'nullable',
                'string',
                'max:1500'
            ],
            'visual_aids' => [
                'sometimes',
                'array'
            ],
            'visual_aids.*.type' => [
                'required_with:visual_aids',
                'string',
                'in:image,chart,diagram,video'
            ],
            'visual_aids.*.url' => [
                'required_with:visual_aids',
                'url'
            ],
            'visual_aids.*.description' => [
                'sometimes',
                'nullable',
                'string',
                'max:255'
            ],
            'is_active' => [
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
            'question_text' => 'question text',
            'question_type' => 'question type',
            'instruction' => 'instruction',
            'preparation_time' => 'preparation time',
            'response_time' => 'response time',
            'difficulty_level' => 'difficulty level',
            'max_score' => 'maximum score',
            'order_index' => 'question order',
            'keywords.*' => 'keyword',
            'sample_response' => 'sample response',
            'evaluation_criteria.fluency' => 'fluency criteria',
            'evaluation_criteria.pronunciation' => 'pronunciation criteria',
            'evaluation_criteria.vocabulary' => 'vocabulary criteria',
            'evaluation_criteria.grammar' => 'grammar criteria',
            'prompts.*' => 'prompt',
            'context_information' => 'context information',
            'visual_aids.*.type' => 'visual aid type',
            'visual_aids.*.url' => 'visual aid URL',
            'visual_aids.*.description' => 'visual aid description',
            'is_active' => 'active status'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question_text.max' => 'Question text cannot exceed 1000 characters.',
            
            'question_type.in' => 'Question type must be one of: describe, narrate, opinion, compare, analyze, present, debate.',
            
            'instruction.max' => 'Instruction cannot exceed 2000 characters.',
            
            'preparation_time.min' => 'Preparation time cannot be negative.',
            'preparation_time.max' => 'Preparation time cannot exceed 5 minutes.',
            
            'response_time.min' => 'Response time must be at least 30 seconds.',
            'response_time.max' => 'Response time cannot exceed 10 minutes.',
            
            'difficulty_level.in' => 'Difficulty level must be beginner, intermediate, or advanced.',
            
            'max_score.min' => 'Maximum score must be at least 1.',
            'max_score.max' => 'Maximum score cannot exceed 100.',
            
            'order_index.min' => 'Question order must be at least 1.',
            
            'keywords.*.max' => 'Each keyword cannot exceed 50 characters.',
            'sample_response.max' => 'Sample response cannot exceed 3000 characters.',
            
            'evaluation_criteria.fluency.max' => 'Fluency criteria cannot exceed 500 characters.',
            'evaluation_criteria.pronunciation.max' => 'Pronunciation criteria cannot exceed 500 characters.',
            'evaluation_criteria.vocabulary.max' => 'Vocabulary criteria cannot exceed 500 characters.',
            'evaluation_criteria.grammar.max' => 'Grammar criteria cannot exceed 500 characters.',
            
            'prompts.*.max' => 'Each prompt cannot exceed 255 characters.',
            'context_information.max' => 'Context information cannot exceed 1500 characters.',
            
            'visual_aids.*.type.required_with' => 'Visual aid type is required.',
            'visual_aids.*.type.in' => 'Visual aid type must be image, chart, diagram, or video.',
            'visual_aids.*.url.required_with' => 'Visual aid URL is required.',
            'visual_aids.*.url.url' => 'Visual aid URL must be a valid URL.',
            'visual_aids.*.description.max' => 'Visual aid description cannot exceed 255 characters.',
        ];
    }
}