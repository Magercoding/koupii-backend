<?php

namespace App\Http\Requests\V1\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class StoreVocabularyRequest extends FormRequest
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
            'category_id' => 'required|exists:vocabulary_categories,id',
            'word' => 'required|string|max:255|unique:vocabularies,word',
            'translation' => 'required|string|max:255',
            'spelling' => 'nullable|string|max:255',
            'explanation' => 'nullable|string',
            'audio_file_path' => 'nullable|file|max:2048|mimetypes:audio/mpeg,audio/wav,audio/ogg',
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Category ID is required',
            'category_id.exists' => 'Category ID not found',
            'word.required' => 'Word is required',
            'word.unique' => 'Vocabulary word already exists',
            'translation.required' => 'Translation is required',
        ];
    }
}
