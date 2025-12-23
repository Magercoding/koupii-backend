<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class RecordingUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the speaking submission
        $submission = $this->route('submission');
        return $submission && auth()->id() === $submission->student_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
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
                'numeric',
                'min:1',
                'max:1800' // 30 minutes max
            ],
            'file_size_bytes' => [
                'sometimes',
                'integer',
                'min:1',
                'max:53687091200' // 50MB in bytes
            ],
            'recording_quality' => [
                'sometimes',
                'string',
                'in:high,medium,low'
            ],
            'sample_rate' => [
                'sometimes',
                'integer',
                'min:8000',
                'max:48000'
            ],
            'bit_rate' => [
                'sometimes',
                'integer',
                'min:32',
                'max:320'
            ],
            'channels' => [
                'sometimes',
                'integer',
                'in:1,2'
            ],
            'recording_device' => [
                'sometimes',
                'string',
                'max:255'
            ],
            'is_final' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'submission_id' => 'submission ID',
            'question_id' => 'question ID',
            'audio_file' => 'audio file',
            'duration_seconds' => 'recording duration',
            'file_size_bytes' => 'file size',
            'recording_quality' => 'recording quality',
            'sample_rate' => 'sample rate',
            'bit_rate' => 'bit rate',
            'channels' => 'audio channels',
            'recording_device' => 'recording device',
            'is_final' => 'final recording flag'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
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
            
            'duration_seconds.numeric' => 'Duration must be a number.',
            'duration_seconds.min' => 'Recording duration must be at least 1 second.',
            'duration_seconds.max' => 'Recording duration must not exceed 30 minutes.',
            
            'file_size_bytes.min' => 'File size must be at least 1 byte.',
            'file_size_bytes.max' => 'File size must not exceed 50MB.',
            
            'recording_quality.in' => 'Recording quality must be high, medium, or low.',
            'sample_rate.min' => 'Sample rate must be at least 8000 Hz.',
            'sample_rate.max' => 'Sample rate must not exceed 48000 Hz.',
            'bit_rate.min' => 'Bit rate must be at least 32 kbps.',
            'bit_rate.max' => 'Bit rate must not exceed 320 kbps.',
            'channels.in' => 'Audio channels must be 1 (mono) or 2 (stereo).',
            'recording_device.max' => 'Recording device information cannot exceed 255 characters.',
        ];
    }
}