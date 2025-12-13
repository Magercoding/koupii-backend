<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;
use App\Helpers\Listening\ListeningQuestionHelper;

class UpdateQuestionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'question_text' => 'sometimes|string|max:2000',
            'question_type' => 'sometimes|string|in:' . implode(',', array_keys(ListeningQuestionHelper::QUESTION_TYPES)),
            'options' => 'nullable|array',
            'correct_answer' => 'sometimes|required',
            'points' => 'nullable|integer|min:1|max:100',
            'order' => 'nullable|integer|min:1',
            'time_limit' => 'nullable|integer|min:10|max:3600',
            'audio_segment' => 'nullable|array',
            'audio_segment.start_time' => 'nullable|numeric|min:0',
            'audio_segment.end_time' => 'nullable|numeric|gt:audio_segment.start_time',
            'audio_segment.transcript' => 'nullable|string',
            'instructions' => 'nullable|string|max:1000',
            'explanation' => 'nullable|string|max:1000',
            'question_options' => 'nullable|array',
            'question_options.*.option_text' => 'required_with:question_options|string|max:500',
            'question_options.*.option_value' => 'required_with:question_options|string|max:200',
            'question_options.*.is_correct' => 'nullable|boolean',
            'question_options.*.order' => 'nullable|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question_text.max' => 'Question text cannot exceed 2000 characters.',
            'question_type.in' => 'Invalid question type selected.',
            'points.min' => 'Points must be at least 1.',
            'points.max' => 'Points cannot exceed 100.',
            'time_limit.min' => 'Time limit must be at least 10 seconds.',
            'time_limit.max' => 'Time limit cannot exceed 1 hour.',
            'audio_segment.end_time.gt' => 'End time must be greater than start time.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $questionType = $this->input('question_type');
            
            if ($questionType) {
                $questionData = $this->validated();
                
                // Validate question data structure for specific type
                if (isset(ListeningQuestionHelper::QUESTION_TYPES[$questionType])) {
                    try {
                        ListeningQuestionHelper::validateQuestionData($questionType, $questionData);
                    } catch (\Exception $e) {
                        $validator->errors()->add('question_structure', $e->getMessage());
                    }
                }
            }
        });
    }
}