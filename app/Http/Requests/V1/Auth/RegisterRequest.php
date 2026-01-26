<?php

namespace App\Http\Requests\V1\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Log;
class RegisterRequest extends BaseRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'
            ],
            'role' => 'required|in:teacher,student,admin',
        ];
    }

    protected function prepareForValidation()
    {
        // No preparation needed
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',

            'email.required' => 'Email is required',
            'email.email' => 'Email must be valid',
            'email.unique' => 'This email is already registered.',

            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character',

            'role.required' => 'Role is required',
            'role.in' => 'Role must be teacher, student, or admin',
        ];
    }




}
