<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpeakingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id' => [
                'required',
                'uuid',
                'exists:student_assignments,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required' => 'Assignment ID is required.',
            'assignment_id.uuid' => 'Assignment ID must be a valid UUID.',
            'assignment_id.exists' => 'The specified assignment does not exist.',
        ];
    }
}