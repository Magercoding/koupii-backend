<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class CreateListeningSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'test_id' => [
                'required',
                'string',
                'exists:tests,id'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'test_id.required' => 'Test ID is required',
            'test_id.string' => 'Test ID must be a valid string',
            'test_id.exists' => 'The specified test does not exist'
        ];
    }
}