<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class SubmitSpeakingRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            // No specific rules needed for submission
            // Validation is handled in the service layer
        ];
    }
}