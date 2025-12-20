<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListeningTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $listeningTask = $this->route('listeningTask');
        return $this->user()->can('update', $listeningTask);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'task_type' => 'sometimes|string|in:conversation,monologue,lecture,discussion,interview,news,announcement,story',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'audio_url' => 'nullable|string|url',
            'audio_duration_seconds' => 'nullable|integer|min:1|max:3600',
            'transcript' => 'nullable|string',
            'audio_segments' => 'nullable|array',
            'audio_segments.*.segment_name' => 'required|string|max:100',
            'audio_segments.*.start_time' => 'required|numeric|min:0',
            'audio_segments.*.end_time' => 'required|numeric|gt:audio_segments.*.start_time',
            'audio_segments.*.description' => 'nullable|string|max:500',
            'audio_segments.*.speaker' => 'nullable|string|max:100',
            'audio_segments.*.accent' => 'nullable|string|max:50',
            'suggest_time_minutes' => 'nullable|integer|min:1|max:180',
            'max_attempts_per_audio' => 'nullable|integer|min:1|max:10',
            'show_transcript' => 'boolean',
            'allow_replay' => 'boolean',
            'replay_settings' => 'nullable|array',
            'replay_settings.max_attempts' => 'nullable|integer|min:1|max:10',
            'replay_settings.replay_delay_seconds' => 'nullable|integer|min:0|max:60',
            'difficulty_level' => 'nullable|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'question_types' => 'nullable|array',
            'question_types.*' => 'string|in:QT1,QT2,QT3,QT4,QT5,QT6,QT7,QT8,QT9,QT10,QT11,QT12,QT13,QT14,QT15'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'task_type.in' => 'Invalid task type selected',
            'title.max' => 'Title must not exceed 255 characters',
            'audio_duration_seconds.max' => 'Audio duration cannot exceed 1 hour',
            'audio_segments.*.end_time.gt' => 'End time must be greater than start time',
            'suggest_time_minutes.max' => 'Suggested time cannot exceed 3 hours',
            'max_attempts_per_audio.max' => 'Maximum attempts cannot exceed 10',
            'difficulty_level.in' => 'Invalid difficulty level selected',
            'question_types.*.in' => 'Invalid question type provided'
        ];
    }
}