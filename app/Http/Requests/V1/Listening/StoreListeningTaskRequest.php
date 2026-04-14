<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class StoreListeningTaskRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string|max:1000',
            'instructions'         => 'nullable|string|max:2000',
            'difficulty'           => 'required|in:beginner,intermediate,advanced',
            'timer_mode'           => 'nullable|in:countdown,countup,none',
            'timer_settings'       => 'nullable|array',
            'timer_settings.hours'   => 'nullable|integer|min:0|max:23',
            'timer_settings.minutes' => 'nullable|integer|min:0|max:59',
            'timer_settings.seconds' => 'nullable|integer|min:0|max:59',
            'allow_repetition'     => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:0|max:10',
            'is_public'            => 'boolean',
            'is_published'         => 'boolean',
            'class_id'             => 'nullable|string|exists:classes,id',
            'due_date'             => 'nullable|date|after:now',

            // Passages structure
            'passages'                                                                          => 'required|array|min:1',
            'passages.*.audio_file'                                                             => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:51200',
            'passages.*.question_groups'                                                        => 'required|array|min:1',
            'passages.*.question_groups.*.instruction'                                          => 'nullable|string',
            'passages.*.question_groups.*.transcript'                                           => 'nullable|array',
            'passages.*.question_groups.*.questions'                                            => 'required|array|min:1',
            'passages.*.question_groups.*.questions.*.question_type'                            => 'required|string|in:choose_correct_answer,choose_multiple_answer,note_completion,sentence_completion,form_completion,summary_completion,map_labeling,table_completion',
            'passages.*.question_groups.*.questions.*.question_text'                            => 'nullable|string',
            'passages.*.question_groups.*.questions.*.question_number'                          => 'nullable|integer|min:1',
            'passages.*.question_groups.*.questions.*.options'                                  => 'nullable|array',
            'passages.*.question_groups.*.questions.*.options.*.option_key'                     => 'required_with:passages.*.question_groups.*.questions.*.options|string',
            'passages.*.question_groups.*.questions.*.options.*.option_text'                    => 'required_with:passages.*.question_groups.*.questions.*.options|string',
            'passages.*.question_groups.*.questions.*.correct_answer'                           => 'nullable',
            'passages.*.question_groups.*.questions.*.points'                                   => 'nullable|numeric|min:0',
            'passages.*.question_groups.*.questions.*.breakdown'                                => 'nullable|array',
            'passages.*.question_groups.*.questions.*.breakdown.explanation'                    => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title'                => 'task title',
            'description'          => 'task description',
            'instructions'         => 'task instructions',
            'difficulty'           => 'difficulty level',
            'timer_mode'           => 'timer mode',
            'timer_settings'       => 'timer settings',
            'allow_repetition'     => 'allow repetition',
            'max_repetition_count' => 'maximum repetition count',
            'is_public'            => 'public status',
            'is_published'         => 'publication status',
            'class_id'             => 'class',
            'due_date'             => 'due date',
            'passages'             => 'passages data',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'difficulty.in'                                                    => 'Difficulty must be one of: beginner, intermediate, advanced',
            'timer_mode.in'                                                    => 'Timer mode must be one of: countdown, countup, none',
            'passages.required'                                                => 'At least one passage with questions is required',
            'passages.*.question_groups.required'                              => 'Each passage must have at least one question group',
            'passages.*.question_groups.*.questions.required'                  => 'Each question group must have at least one question',
            'passages.*.question_groups.*.questions.*.question_type.in'        => 'Question type must be one of the valid listening question types',
            'passages.*.audio_file.mimes'                                      => 'Audio file must be mp3, wav, ogg, or m4a',
            'passages.*.audio_file.max'                                        => 'Audio file cannot exceed 50 MB',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Casts boolean fields sent as "on"/""/"1"/"0" strings from multipart/form-data,
     * and normalises timer_settings when sent as a JSON string.
     */
    protected function prepareForValidation(): void
    {
        $merge = [
            'created_by'       => $this->user()->id,
            'allow_repetition' => $this->boolean('allow_repetition'),
            'is_public'        => $this->boolean('is_public'),
            'is_published'     => $this->boolean('is_published'),
        ];

        // timer_settings may arrive as a JSON string (e.g. from non-FormData clients)
        if ($this->has('timer_settings') && is_string($this->input('timer_settings'))) {
            $decoded = json_decode($this->input('timer_settings'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $merge['timer_settings'] = $decoded;
            }
        }

        $this->merge($merge);
    }
}
