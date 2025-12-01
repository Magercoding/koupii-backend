<?php

namespace App\Http\Requests\V1\Class;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class ClassInvitationRequest extends BaseRequest
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
            'class_code' => 'required|exists:classes,class_code',
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'class_code.required' => 'Class code is required.',
            'class_code.exists' => 'Class code does not exist.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid address.',
        ];
    }
}
