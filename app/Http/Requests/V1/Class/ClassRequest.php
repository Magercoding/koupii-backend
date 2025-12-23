<?php

namespace App\Http\Requests\V1\Class;

use App\Http\Requests\BaseRequest;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return auth()->check() && auth()->user()->role === 'teacher';
        return true ; 
    }

    protected function prepareForValidation()
    {
        // Convert string boolean values to actual boolean for proper validation
        if ($this->has('is_active')) {
            $isActive = $this->input('is_active');
            
            // Handle various boolean representations
            if (is_string($isActive)) {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            
            $this->merge([
                'is_active' => $isActive
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        $id = $this->route('id'); // for update

        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',

            'class_code'  => 'nullable|string|max:50|unique:classes,class_code,' . ($id ?? 'NULL'),

            'cover_image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg|max:10240',

            'is_active'   => 'nullable|boolean',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Class name is required.',
            'class_code.unique' => 'Class code already exists.',
            'cover_image.mimetypes' => 'Cover must be an image (jpg, png).',
            'cover_image.max' => 'Cover max size is 10MB.',
            'is_active.boolean' => 'The is active field must be true or false.',
        ];
    }
}
