<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class SubmitListeningRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'student';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'submission_text' => 'nullable|string',
            'file_path' => 'nullable|string|max:500',
            'file_original_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:0',
            'file_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:listening_questions,id',
            'answers.*.answer' => 'nullable|string|max:1000',
            'answers.*.is_correct' => 'nullable|boolean',
            'time_spent_seconds' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'submission_text' => 'submission text',
            'file_path' => 'file path',
            'file_original_name' => 'original file name',
            'file_size' => 'file size',
            'file_type' => 'file type',
            'notes' => 'submission notes',
            'answers' => 'answers',
            'answers.*.question_id' => 'question ID',
            'answers.*.answer' => 'answer text',
            'time_spent_seconds' => 'time spent',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'answers.*.question_id.required' => 'Each answer must have a valid question ID',
            'answers.*.question_id.exists' => 'The question ID does not exist',
            'file_size.min' => 'File size must be positive',
            'time_spent_seconds.min' => 'Time spent must be positive',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id' => $this->user()->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);
    }
}