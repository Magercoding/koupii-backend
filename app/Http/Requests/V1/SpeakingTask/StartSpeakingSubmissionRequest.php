<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class StartSpeakingSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can access the speaking task assignment
        return $this->user()->can('view', $this->route('assignment'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'attempt_number' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10'
            ],
            'device_info' => [
                'sometimes',
                'array'
            ],
            'device_info.browser' => [
                'nullable',
                'string',
                'max:255'
            ],
            'device_info.os' => [
                'nullable',
                'string',
                'max:255'
            ],
            'device_info.microphone' => [
                'nullable',
                'string',
                'max:255'
            ],
            'device_info.audio_quality' => [
                'nullable',
                'string',
                'in:high,medium,low'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'attempt_number' => 'attempt number',
            'device_info.browser' => 'browser information',
            'device_info.os' => 'operating system',
            'device_info.microphone' => 'microphone information',
            'device_info.audio_quality' => 'audio quality',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'attempt_number.min' => 'Attempt number must be at least 1.',
            'attempt_number.max' => 'Attempt number cannot exceed 10.',
            'device_info.browser.max' => 'Browser information cannot exceed 255 characters.',
            'device_info.os.max' => 'Operating system information cannot exceed 255 characters.',
            'device_info.microphone.max' => 'Microphone information cannot exceed 255 characters.',
            'device_info.audio_quality.in' => 'Audio quality must be high, medium, or low.',
        ];
    }
}
