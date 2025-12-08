<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class StartSpeakingSubmissionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attempt_number' => 'integer|min:1',
        ];
    }
}
