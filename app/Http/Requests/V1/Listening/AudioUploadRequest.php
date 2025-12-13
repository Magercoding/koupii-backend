<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class AudioUploadRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac,ogg,flac|max:51200', // 50MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'language' => 'nullable|string|size:2',
            'auto_process' => 'nullable|boolean',
            'generate_transcript' => 'nullable|boolean',
            'generate_segments' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'audio_file.required' => 'Audio file is required.',
            'audio_file.file' => 'Upload must be a valid file.',
            'audio_file.mimes' => 'Audio file must be in MP3, WAV, M4A, AAC, OGG, or FLAC format.',
            'audio_file.max' => 'Audio file size cannot exceed 50MB.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'language.size' => 'Language code must be exactly 2 characters (e.g., en, fr, es).'
        ];
    }
}