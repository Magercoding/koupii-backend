<?php

namespace App\Http\Requests\V1\Class;

use App\Http\Requests\BaseRequest;


class ClassEnrollmentRequest extends BaseRequest
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
            'status' => 'sometimes|in:active,inactive,pending',
            'enrolled_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'status.sometimes' => 'Status field is optional',
            'status.in' => 'Status must be one of: active, inactive, pending',

            'enrolled_at.date' => 'Enrolled at must be a valid date',
        ];
    }
}
