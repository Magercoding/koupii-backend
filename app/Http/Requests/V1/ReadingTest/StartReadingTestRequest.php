<?php

namespace App\Http\Requests\V1\ReadingTest;

use App\Http\Requests\BaseRequest;

class StartReadingTestRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'attempt_number' => 'integer|min:1|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'attempt_number.integer' => 'Attempt number must be a valid number',
            'attempt_number.min' => 'Attempt number must be at least 1',
            'attempt_number.max' => 'Maximum 10 attempts allowed',
        ];
    }
}