<?php

namespace App\Http\Requests\V1\Vocabulary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVocabularyRequest extends FormRequest
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
            'category_id' => 'sometimes|exists:vocabulary_categories,id',
            'word' => 'sometimes|string|max:255',
            'translation' => 'sometimes|string|max:255',
            'spelling' => 'nullable|string|max:255',
            'explanation' => 'nullable|string',

            'audio_file_path' => [
                'nullable',
                'file',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!in_array(strtolower($value->getClientOriginalExtension()), ['mp3', 'wav', 'ogg'])) {
                        $fail('The file must be an audio file like MP3, WAV, or OGG.');
                    }
                },
            ],

            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Category ID not found',
        ];
    }
}
