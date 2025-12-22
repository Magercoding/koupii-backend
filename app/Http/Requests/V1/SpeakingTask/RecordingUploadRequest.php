<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class RecordingUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_id' => [
                'required',
                'uuid',
                'exists:speaking_submissions,id'
            ],
            'question_id' => [
                'required',
                'uuid',
                'exists:speaking_questions,id'
            ],
            'audio_file' => [
                'required',
                'file',
                'mimes:mp3,wav,m4a,aac,ogg,webm',
                'max:51200' // 50MB max
            ],
            'duration_seconds' => [
                'sometimes',
                'integer',
                'min:1',
                'max:1800' // 30 minutes max
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_id.required' => 'Submission ID is required.',
            'submission_id.uuid' => 'Submission ID must be a valid UUID.',
            'submission_id.exists' => 'The specified submission does not exist.',
            
            'question_id.required' => 'Question ID is required.',
            'question_id.uuid' => 'Question ID must be a valid UUID.',
            'question_id.exists' => 'The specified question does not exist.',
            
            'audio_file.required' => 'Audio file is required.',
            'audio_file.file' => 'Audio file must be a valid file.',
            'audio_file.mimes' => 'Audio file must be one of the following types: mp3, wav, m4a, aac, ogg, webm.',
            'audio_file.max' => 'Audio file must not be larger than 50MB.',
            
            'duration_seconds.integer' => 'Duration must be an integer.',
            'duration_seconds.min' => 'Duration must be at least 1 second.',
            'duration_seconds.max' => 'Duration must not exceed 30 minutes.',
        ];
    }
}