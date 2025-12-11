<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class LogAudioPlayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('submission')->student_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'audio_segment_id' => 'required|uuid|exists:listening_audio_segments,id',
            'question_id' => 'nullable|uuid|exists:test_questions,id',
            'start_time' => 'nullable|numeric|min:0',
            'end_time' => 'nullable|numeric|min:0',
            'duration' => 'nullable|numeric|min:0|max:7200', // Max 2 hours
            'play_position' => 'nullable|numeric|min:0',
            'user_action' => 'nullable|string|in:play,pause,seek,replay,stop',
            'device_info' => 'nullable|array',
            'device_info.volume' => 'nullable|numeric|min:0|max:1',
            'device_info.playback_rate' => 'nullable|numeric|min:0.25|max:2.0'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'audio_segment_id' => 'audio segment ID',
            'question_id' => 'question ID',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'duration' => 'duration',
            'play_position' => 'play position',
            'user_action' => 'user action',
            'device_info.volume' => 'volume',
            'device_info.playback_rate' => 'playback rate'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'audio_segment_id.required' => 'Audio segment ID is required.',
            'audio_segment_id.exists' => 'The selected audio segment does not exist.',
            'question_id.exists' => 'The selected question does not exist.',
            'start_time.min' => 'Start time cannot be negative.',
            'end_time.min' => 'End time cannot be negative.',
            'duration.min' => 'Duration cannot be negative.',
            'duration.max' => 'Duration cannot exceed 2 hours.',
            'play_position.min' => 'Play position cannot be negative.',
            'user_action.in' => 'User action must be one of: play, pause, seek, replay, stop.',
            'device_info.volume.min' => 'Volume cannot be less than 0.',
            'device_info.volume.max' => 'Volume cannot be greater than 1.',
            'device_info.playback_rate.min' => 'Playback rate cannot be less than 0.25.',
            'device_info.playback_rate.max' => 'Playback rate cannot be greater than 2.0.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Calculate duration if not provided but start_time and end_time are available
        if (!$this->has('duration') && $this->has('start_time') && $this->has('end_time')) {
            $duration = max(0, $this->end_time - $this->start_time);
            $this->merge(['duration' => $duration]);
        }

        // Default values
        if (!$this->has('start_time')) {
            $this->merge(['start_time' => 0]);
        }

        if (!$this->has('end_time')) {
            $this->merge(['end_time' => 0]);
        }

        if (!$this->has('duration')) {
            $this->merge(['duration' => 0]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that end_time is not less than start_time
            if ($this->has('start_time') && $this->has('end_time') && 
                $this->end_time < $this->start_time) {
                $validator->errors()->add('end_time', 'End time cannot be less than start time.');
            }

            // Validate duration consistency
            if ($this->has('start_time') && $this->has('end_time') && $this->has('duration')) {
                $calculatedDuration = $this->end_time - $this->start_time;
                $tolerance = 1; // 1 second tolerance
                
                if (abs($this->duration - $calculatedDuration) > $tolerance) {
                    $validator->errors()->add('duration', 'Duration does not match the time range.');
                }
            }
        });
    }
}