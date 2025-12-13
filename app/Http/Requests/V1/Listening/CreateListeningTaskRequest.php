<?php

namespace App\Http\Requests\V1\Listening;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ListeningTask;

class CreateListeningTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ListeningTask::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'test_id' => 'required|string|exists:tests,id',
            'task_type' => 'required|string|in:conversation,monologue,lecture,discussion,interview,news,announcement,story',
            'title' => 'required|string|max:255',
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
            'test_id.required' => 'Test ID is required',
            'test_id.exists' => 'Selected test does not exist',
            'task_type.required' => 'Task type is required',
            'task_type.in' => 'Invalid task type selected',
            'title.required' => 'Title is required',
            'title.max' => 'Title must not exceed 255 characters',
            'audio_duration_seconds.max' => 'Audio duration cannot exceed 1 hour',
            'audio_segments.*.end_time.gt' => 'End time must be greater than start time',
            'suggest_time_minutes.max' => 'Suggested time cannot exceed 3 hours',
            'max_attempts_per_audio.max' => 'Maximum attempts cannot exceed 10',
            'difficulty_level.in' => 'Invalid difficulty level selected',
            'question_types.*.in' => 'Invalid question type provided'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate audio segments consistency
            if ($this->has('audio_segments') && is_array($this->audio_segments)) {
                $this->validateAudioSegments($validator);
            }

            // Validate question types compatibility
            if ($this->has('question_types') && is_array($this->question_types)) {
                $this->validateQuestionTypes($validator);
            }
        });
    }

    /**
     * Validate audio segments for consistency
     */
    private function validateAudioSegments($validator): void
    {
        $segments = $this->audio_segments;
        $audioDuration = $this->audio_duration_seconds;

        foreach ($segments as $index => $segment) {
            // Check if end time exceeds audio duration
            if ($audioDuration && isset($segment['end_time']) && $segment['end_time'] > $audioDuration) {
                $validator->errors()->add(
                    "audio_segments.{$index}.end_time",
                    'End time cannot exceed total audio duration'
                );
            }

            // Check for overlapping segments
            foreach ($segments as $otherIndex => $otherSegment) {
                if ($index !== $otherIndex && isset($segment['start_time'], $segment['end_time'], $otherSegment['start_time'], $otherSegment['end_time'])) {
                    if ($this->segmentsOverlap($segment, $otherSegment)) {
                        $validator->errors()->add(
                            "audio_segments.{$index}",
                            'Audio segments cannot overlap with each other'
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * Check if two audio segments overlap
     */
    private function segmentsOverlap(array $segment1, array $segment2): bool
    {
        return !($segment1['end_time'] <= $segment2['start_time'] || $segment2['end_time'] <= $segment1['start_time']);
    }

    /**
     * Validate question types compatibility
     */
    private function validateQuestionTypes($validator): void
    {
        $questionTypes = $this->question_types;
        $taskType = $this->task_type;

        // Define incompatible combinations
        $incompatibleCombinations = [
            // Real-time audio interaction types shouldn't be mixed with complex completion types
            'realtime_with_complex' => [
                'realtime' => ['QT14', 'QT15'], // gap_fill_listening, audio_dictation
                'complex' => ['QT4', 'QT8', 'QT9'] // table_completion, note_completion, flowchart_completion
            ]
        ];

        $hasRealtime = !empty(array_intersect($questionTypes, $incompatibleCombinations['realtime_with_complex']['realtime']));
        $hasComplex = !empty(array_intersect($questionTypes, $incompatibleCombinations['realtime_with_complex']['complex']));

        if ($hasRealtime && $hasComplex) {
            $validator->errors()->add(
                'question_types',
                'Real-time audio interaction questions (QT14, QT15) cannot be combined with complex completion questions (QT4, QT8, QT9)'
            );
        }

        // Validate task type compatibility
        $this->validateTaskTypeCompatibility($validator, $taskType, $questionTypes);
    }

    /**
     * Validate question types compatibility with task type
     */
    private function validateTaskTypeCompatibility($validator, string $taskType, array $questionTypes): void
    {
        $taskTypeCompatibility = [
            'conversation' => ['QT1', 'QT2', 'QT6', 'QT7', 'QT13', 'QT14', 'QT15'],
            'monologue' => ['QT1', 'QT2', 'QT4', 'QT5', 'QT6', 'QT8', 'QT9', 'QT10', 'QT13'],
            'lecture' => ['QT1', 'QT2', 'QT4', 'QT5', 'QT8', 'QT9', 'QT10', 'QT12', 'QT13'],
            'discussion' => ['QT1', 'QT2', 'QT6', 'QT12', 'QT13'],
            'interview' => ['QT1', 'QT2', 'QT6', 'QT7', 'QT13'],
            'news' => ['QT1', 'QT2', 'QT5', 'QT6', 'QT10', 'QT13'],
            'announcement' => ['QT1', 'QT6', 'QT7', 'QT13'],
            'story' => ['QT1', 'QT2', 'QT5', 'QT6', 'QT10', 'QT13']
        ];

        $compatibleTypes = $taskTypeCompatibility[$taskType] ?? [];
        $incompatibleTypes = array_diff($questionTypes, $compatibleTypes);

        if (!empty($incompatibleTypes)) {
            $validator->errors()->add(
                'question_types',
                "Question types " . implode(', ', $incompatibleTypes) . " are not compatible with task type '{$taskType}'"
            );
        }
    }
}