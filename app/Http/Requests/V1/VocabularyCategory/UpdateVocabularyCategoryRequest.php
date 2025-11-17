<?php

namespace App\Http\Requests\V1\VocabularyCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVocabularyCategoryRequest extends FormRequest
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
        $id = $this->route('id'); // ambil ID dari route

        return [
            'name' => "required|string|max:255|unique:vocabulary_categories,name,$id",
            'color_code' => 'nullable|string|max:20',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required',
            'name.unique' => 'Category name already exists',
        ];
    }
}
