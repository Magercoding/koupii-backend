<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class UploadRecordingRequest extends BaseRequest
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
            'question_id' => 'required|exists:speaking_questions,id',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:51200', // 50MB max
            'duration_seconds' => 'nullable|integer|min:1',
            'recording_started_at' => 'nullable|date',
            'recording_ended_at' => 'nullable|date',
        ];
    }
}
