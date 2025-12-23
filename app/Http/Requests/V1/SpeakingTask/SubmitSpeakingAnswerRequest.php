<?php

namespace App\Http\Requests\V1\SpeakingTask;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSpeakingAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user owns the submission
        $submission = $this->route('submission');
        return $submission && auth()->id() === $submission->student_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'question_id' => [
                'required',
                'uuid',
                'exists:speaking_questions,id'
            ],
            'audio_url' => [
                'required',
                'url'
            ],
            'transcript' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000'
            ],
            'duration_seconds' => [
                'required',
                'numeric',
                'min:1',
                'max:1800' // 30 minutes max
            ],
            'confidence_score' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:1'
            ],
            'response_quality' => [
                'sometimes',
                'string',
                'in:excellent,good,fair,poor'
            ],
            'preparation_time_used' => [
                'sometimes',
                'integer',
                'min:0',
                'max:300' // 5 minutes max
            ],
            'attempt_count' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5'
            ],
            'is_final' => [
                'sometimes',
                'boolean'
            ],
            'audio_metadata' => [
                'sometimes',
                'array'
            ],
            'audio_metadata.sample_rate' => [
                'sometimes',
                'integer',
                'min:8000',
                'max:48000'
            ],
            'audio_metadata.bit_rate' => [
                'sometimes',
                'integer',
                'min:32',
                'max:320'
            ],
            'audio_metadata.channels' => [
                'sometimes',
                'integer',
                'in:1,2'
            ],
            'audio_metadata.format' => [
                'sometimes',
                'string',
                'in:mp3,wav,m4a,aac,ogg,webm'
            ],
            'audio_metadata.file_size_bytes' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'speech_analysis' => [
                'sometimes',
                'array'
            ],
            'speech_analysis.speaking_rate' => [
                'sometimes',
                'numeric',
                'min:0'
            ],
            'speech_analysis.pause_count' => [
                'sometimes',
                'integer',
                'min:0'
            ],
            'speech_analysis.pause_duration' => [
                'sometimes',
                'numeric',
                'min:0'
            ],
            'speech_analysis.volume_level' => [
                'sometimes',
                'string',
                'in:very_low,low,normal,high,very_high'
            ],
            'student_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'question_id' => 'question ID',
            'audio_url' => 'audio URL',
            'transcript' => 'transcript',
            'duration_seconds' => 'duration',
            'confidence_score' => 'confidence score',
            'response_quality' => 'response quality',
            'preparation_time_used' => 'preparation time used',
            'attempt_count' => 'attempt count',
            'is_final' => 'final submission flag',
            'audio_metadata.sample_rate' => 'sample rate',
            'audio_metadata.bit_rate' => 'bit rate',
            'audio_metadata.channels' => 'audio channels',
            'audio_metadata.format' => 'audio format',
            'audio_metadata.file_size_bytes' => 'file size',
            'speech_analysis.speaking_rate' => 'speaking rate',
            'speech_analysis.pause_count' => 'pause count',
            'speech_analysis.pause_duration' => 'pause duration',
            'speech_analysis.volume_level' => 'volume level',
            'student_notes' => 'student notes'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required.',
            'question_id.exists' => 'The specified question does not exist.',
            
            'audio_url.required' => 'Audio URL is required.',
            'audio_url.url' => 'Audio URL must be a valid URL.',
            
            'transcript.max' => 'Transcript cannot exceed 5000 characters.',
            
            'duration_seconds.required' => 'Audio duration is required.',
            'duration_seconds.min' => 'Audio duration must be at least 1 second.',
            'duration_seconds.max' => 'Audio duration cannot exceed 30 minutes.',
            
            'confidence_score.min' => 'Confidence score must be between 0 and 1.',
            'confidence_score.max' => 'Confidence score must be between 0 and 1.',
            
            'response_quality.in' => 'Response quality must be excellent, good, fair, or poor.',
            
            'preparation_time_used.min' => 'Preparation time cannot be negative.',
            'preparation_time_used.max' => 'Preparation time cannot exceed 5 minutes.',
            
            'attempt_count.min' => 'Attempt count must be at least 1.',
            'attempt_count.max' => 'Attempt count cannot exceed 5.',
            
            'audio_metadata.sample_rate.min' => 'Sample rate must be at least 8000 Hz.',
            'audio_metadata.sample_rate.max' => 'Sample rate cannot exceed 48000 Hz.',
            'audio_metadata.bit_rate.min' => 'Bit rate must be at least 32 kbps.',
            'audio_metadata.bit_rate.max' => 'Bit rate cannot exceed 320 kbps.',
            'audio_metadata.channels.in' => 'Audio channels must be 1 (mono) or 2 (stereo).',
            'audio_metadata.format.in' => 'Audio format must be mp3, wav, m4a, aac, ogg, or webm.',
            'audio_metadata.file_size_bytes.min' => 'File size must be at least 1 byte.',
            
            'speech_analysis.speaking_rate.min' => 'Speaking rate cannot be negative.',
            'speech_analysis.pause_count.min' => 'Pause count cannot be negative.',
            'speech_analysis.pause_duration.min' => 'Pause duration cannot be negative.',
            'speech_analysis.volume_level.in' => 'Volume level must be very_low, low, normal, high, or very_high.',
            
            'student_notes.max' => 'Student notes cannot exceed 1000 characters.',
        ];
    }
}