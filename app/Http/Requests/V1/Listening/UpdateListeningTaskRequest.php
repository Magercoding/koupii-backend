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

        // If route model binding resolved a ListeningTask, use policy
        if ($listeningTask instanceof \App\Models\ListeningTask) {
            $user = $this->user();
            return $user && ($user->role === 'admin' || ($user->role === 'teacher' && $listeningTask->created_by === $user->id));
        }

        // If it's a string ID, find the model and check
        if (is_string($listeningTask)) {
            $task = \App\Models\ListeningTask::find($listeningTask);
            if (!$task) {
                return false;
            }
            $user = $this->user();
            return $user && ($user->role === 'admin' || ($user->role === 'teacher' && $task->created_by === $user->id));
        }

        // Fallback: allow admins and teachers
        return $this->user() && in_array($this->user()->role, ['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic task info
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|in:listening',
            'difficulty' => 'nullable|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'test_type' => 'nullable|string|in:single,multiple',
            'timer_mode' => 'nullable|string|in:none,timer,countdown,countup',
            'timer_settings' => 'nullable|array',
            'timer_settings.hours' => 'nullable|integer|min:0',
            'timer_settings.minutes' => 'nullable|integer|min:0',
            'timer_settings.seconds' => 'nullable|integer|min:0',
            'allow_repetition' => 'nullable',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'is_public' => 'nullable',
            'is_published' => 'nullable',
            'due_date' => 'nullable|date',
            'max_retake_attempts' => 'nullable|integer|min:1|max:10',
            'settings' => 'nullable|array',
            'settings.shuffle_questions' => 'nullable',
            'class_id' => 'nullable|string|uuid',

            // Passages structure (nested data from frontend)
            'passages' => 'sometimes|array',
            'passages.*.audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:51200', // 50MB
            'passages.*.question_groups' => 'nullable|array',
            'passages.*.question_groups.*.instruction' => 'nullable|string|max:2000',
            'passages.*.question_groups.*.transcript' => 'nullable|array',
            'passages.*.question_groups.*.transcript.type' => 'nullable|string|in:descriptive,transcript,conversation',
            'passages.*.question_groups.*.transcript.title' => 'nullable|string|max:500',
            'passages.*.question_groups.*.transcript.text' => 'nullable|string',
            'passages.*.question_groups.*.transcript.speakers' => 'nullable|array',
            'passages.*.question_groups.*.image' => 'nullable|array',
            'passages.*.question_groups.*.image.file' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120', // 5MB
            'passages.*.question_groups.*.image.title' => 'nullable|string|max:255',
            'passages.*.question_groups.*.questions' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.question_type' => 'required_with:passages|string',
            'passages.*.question_groups.*.questions.*.question_number' => 'nullable|integer',
            'passages.*.question_groups.*.questions.*.question_text' => 'nullable|string',
            'passages.*.question_groups.*.questions.*.points' => 'nullable|numeric|min:0',
            'passages.*.question_groups.*.questions.*.options' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.correct_answer' => 'nullable',
            'passages.*.question_groups.*.questions.*.breakdown' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.items' => 'nullable|array',
            'passages.*.question_groups.*.questions.*.question_data' => 'nullable|array',

            // Legacy flat fields (kept for backward compatibility)
            'task_type' => 'nullable|string',
            'audio_url' => 'nullable|string|url',
            'audio_duration_seconds' => 'nullable|integer|min:1|max:3600',
            'transcript' => 'nullable|string',
            'audio_segments' => 'nullable|array',
            'difficulty_level' => 'nullable|string|in:beginner,elementary,intermediate,upper_intermediate,advanced,proficiency',
            'question_types' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title must not exceed 255 characters',
            'passages.*.audio_file.max' => 'Audio file size cannot exceed 50MB',
            'passages.*.audio_file.mimes' => 'Audio file must be in MP3, WAV, OGG, or M4A format',
            'passages.*.question_groups.*.image.file.max' => 'Image size cannot exceed 5MB',
            'passages.*.question_groups.*.image.file.mimes' => 'Image must be JPEG, PNG, or WEBP',
        ];
    }

    /**
     * Prepare the data for validation.
     * Casts boolean fields sent as "on"/""/1/0 strings from multipart/form-data.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['is_published', 'is_public', 'allow_repetition'] as $field) {
            if ($this->has($field)) {
                $val = $this->input($field);
                $merge[$field] = in_array($val, ['on', true, 1, '1', 'true'], true);
            }
        }

        // Normalise timer_mode: "notimer" -> "none"
        if ($this->has('timer_mode') && $this->input('timer_mode') === 'notimer') {
            $merge['timer_mode'] = 'none';
        }

        // timer_settings may arrive as a JSON string
        if ($this->has('timer_settings') && is_string($this->input('timer_settings'))) {
            $decoded = json_decode($this->input('timer_settings'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['timer_settings'] = $decoded;
            }
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}