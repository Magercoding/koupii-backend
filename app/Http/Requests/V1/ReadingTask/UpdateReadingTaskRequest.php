<?php

namespace App\Http\Requests\V1\ReadingTask;

use App\Http\Requests\BaseRequest;

class UpdateReadingTaskRequest extends BaseRequest
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string|max:2000',
            'type' => 'sometimes|required|in:reading,listening,speaking,writing',
            'difficulty' => 'sometimes|required|in:beginner,intermediate,advanced',
            'test_type' => 'sometimes|required|in:single,final',
            'timer_mode' => 'nullable|in:countdown,countup,none',
            'timer_settings' => 'nullable|string',
            'allow_repetition' => 'boolean',
            'max_repetition_count' => 'nullable|integer|min:0|max:10',
            'is_public' => 'boolean',
            'is_published' => 'boolean',
            'settings' => 'nullable|string',
            'passages' => 'sometimes|required|string', // JSON string of passages array
            'passage_images' => 'nullable|array',
            'passage_images.*' => 'file|mimes:jpg,jpeg,png,gif|max:5120', // 5MB max
            'reference_materials' => 'nullable|array',
            'reference_materials.*' => 'file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'task title',
            'description' => 'task description',
            'instructions' => 'task instructions',
            'type' => 'task type',
            'difficulty' => 'difficulty level',
            'test_type' => 'test type',
            'timer_mode' => 'timer mode',
            'timer_settings' => 'timer settings',
            'allow_repetition' => 'allow repetition',
            'max_repetition_count' => 'maximum repetition count',
            'is_public' => 'public status',
            'is_published' => 'publication status',
            'settings' => 'task settings',
            'passages' => 'passages data',
            'passage_images' => 'passage images',
            'reference_materials' => 'reference materials',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be one of: reading, listening, speaking, writing',
            'difficulty.in' => 'Difficulty must be one of: beginner, intermediate, advanced',
            'test_type.in' => 'Test type must be one of: single, final',
            'timer_mode.in' => 'Timer mode must be one of: countdown, countup, none',
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 10',
            'passage_images.*.mimes' => 'Passage images must be jpg, jpeg, png, or gif files',
            'passage_images.*.max' => 'Passage images cannot exceed 5MB',
            'reference_materials.*.mimes' => 'Reference materials must be pdf, doc, docx, or txt files',
            'reference_materials.*.max' => 'Reference materials cannot exceed 10MB',
            'passages.required' => 'At least one passage with questions is required',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        if ($this->has('allow_repetition')) {
            $data['allow_repetition'] = $this->boolean('allow_repetition');
        }
        
        if ($this->has('is_public')) {
            $data['is_public'] = $this->boolean('is_public');
        }
        
        if ($this->has('is_published')) {
            $data['is_published'] = $this->boolean('is_published');
        }

        $this->merge($data);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate passages JSON structure if provided
            if ($this->filled('passages')) {
                try {
                    $passages = json_decode($this->passages, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add('passages', 'Passages must be valid JSON');
                        return;
                    }

                    if (!is_array($passages) || empty($passages)) {
                        $validator->errors()->add('passages', 'At least one passage is required');
                        return;
                    }

                    foreach ($passages as $index => $passage) {
                        if (!isset($passage['question_groups']) || !is_array($passage['question_groups']) || empty($passage['question_groups'])) {
                            $validator->errors()->add("passages.{$index}.question_groups", 'Each passage must have at least one question group');
                        }

                        if (isset($passage['question_groups'])) {
                            foreach ($passage['question_groups'] as $groupIndex => $group) {
                                if (!isset($group['questions']) || !is_array($group['questions']) || empty($group['questions'])) {
                                    $validator->errors()->add("passages.{$index}.question_groups.{$groupIndex}.questions", 'Each question group must have at least one question');
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('passages', 'Invalid passages data structure');
                }
            }
        });
    }
}