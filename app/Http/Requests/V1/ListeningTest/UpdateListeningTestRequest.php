<?php

namespace App\Http\Requests\V1\ListeningTest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListeningTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        $test = $this->route('id') ? \App\Models\Test::find($this->route('id')) : null;

        return auth()->check() && (
            auth()->user()->role === 'admin' ||
            ($test && $test->creator_id === auth()->id())
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'test_type' => 'sometimes|required|in:academic,general,business,ielts,toefl',
            'difficulty' => 'sometimes|required|in:beginner,intermediate,advanced',
            'timer_mode' => 'nullable|in:none,test,practice',
            'timer_settings' => 'nullable|array',
            'timer_settings.test_time' => 'nullable|integer|min:1|max:480',
            'timer_settings.warning_time' => 'nullable|integer|min:1',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:1|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|array',
            'settings.instructions' => 'nullable|string',
            'settings.audio_format' => 'nullable|string',
            'settings.audio_speed' => 'nullable|numeric|min:0.5|max:2.0',
            'settings.cover_image' => 'nullable|string',
            'settings.tags' => 'nullable|array',
            'settings.tags.*' => 'string|max:50',

            // Listening audio segments (optional for updates)
            'audio_segments' => 'sometimes|array|min:1',
            'audio_segments.*.id' => 'nullable|exists:listening_audio_segments,id',
            'audio_segments.*.title' => 'required|string|max:255',
            'audio_segments.*.audio_url' => 'required|string',
            'audio_segments.*.transcript' => 'nullable|string',
            'audio_segments.*.duration' => 'nullable|integer|min:1',
            'audio_segments.*.segment_type' => 'required|in:conversation,lecture,monologue,dialogue',
            'audio_segments.*.difficulty_level' => 'nullable|in:beginner,intermediate,advanced',

            // Listening questions (optional for updates)
            'questions' => 'sometimes|array|min:1',
            'questions.*.id' => 'nullable|exists:listening_questions,id',
            'questions.*.audio_segment_id' => 'required|string',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:multiple_choice,fill_blank,matching,true_false,short_answer',
            'questions.*.time_range' => 'nullable|array',
            'questions.*.time_range.start' => 'nullable|integer|min:0',
            'questions.*.time_range.end' => 'nullable|integer|min:0',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.options.*.text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
            'questions.*.correct_answer' => 'nullable|string',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages()
    {
        return [
            'title.required' => 'Test title is required',
            'description.required' => 'Test description is required', 
            'test_type.in' => 'Test type must be academic, general, business, ielts, or toefl',
            'difficulty.in' => 'Difficulty must be beginner, intermediate, or advanced',
            'timer_mode.in' => 'Timer mode must be none, test, or practice',
            'audio_segments.*.title.required' => 'Audio segment title is required',
            'audio_segments.*.audio_url.required' => 'Audio URL is required',
            'audio_segments.*.segment_type.in' => 'Segment type must be conversation, lecture, monologue, or dialogue',
            'questions.*.question_text.required' => 'Question text is required',
            'questions.*.question_type.in' => 'Question type must be multiple_choice, fill_blank, matching, true_false, or short_answer',
            'questions.*.options.required_if' => 'Options are required for multiple choice questions',
        ];
    }
}