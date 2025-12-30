<?php

namespace App\Http\Requests\V1\User;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow authenticated users to update their profile
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules() : array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email',
            'role' => 'sometimes|required|in:teacher,student,admin',
            'avatar' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg|max:10240',
            'bio' => 'nullable|string',
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'role.required' => 'Role is required',
            'role.in' => 'Role must be teacher, student, or admin',
            'avatar.file' => 'Avatar must be a file',
            'avatar.mimetypes' => 'Avatar must be a JPEG, PNG, or JPG file',
            'avatar.max' => 'Avatar size must be at most 10MB',
            'bio.string' => 'Bio must be a string',
        ];
    }
}
