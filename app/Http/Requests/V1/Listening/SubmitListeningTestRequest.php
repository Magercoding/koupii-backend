<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class SubmitListeningTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('submission')->student_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'force_submit' => 'boolean',
            'time_spent' => 'nullable|integer|min:0',
            'completion_notes' => 'nullable|string|max:1000',
            'final_review' => 'nullable|boolean'
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
            'completion_notes.max' => 'Completion notes cannot exceed 1000 characters.',
        ];
    }
}