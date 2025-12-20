<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class StartListeningTestRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'timezone' => 'nullable|string|timezone',
            'user_agent' => 'nullable|string|max:500',
            'device_info' => 'nullable|array',
            'device_info.platform' => 'nullable|string|max:100',
            'device_info.browser' => 'nullable|string|max:100',
            'device_info.screen_resolution' => 'nullable|string|max:50'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'timezone' => 'timezone',
            'user_agent' => 'user agent',
            'device_info.platform' => 'device platform',
            'device_info.browser' => 'browser',
            'device_info.screen_resolution' => 'screen resolution'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'timezone.timezone' => 'Please provide a valid timezone.',
            'user_agent.max' => 'User agent information is too long.',
            'device_info.platform.max' => 'Platform information is too long.',
            'device_info.browser.max' => 'Browser information is too long.',
            'device_info.screen_resolution.max' => 'Screen resolution information is too long.'
        ];
    }
}