<?php

namespace App\Http\Requests\V1\Test;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends BaseRequest
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
            'description' => 'sometimes|nullable|string|max:1000',
            'type' => ['sometimes', 'required', Rule::in(['reading', 'listening', 'speaking', 'writing'])],
            'difficulty' => ['sometimes', 'required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'test_type' => ['sometimes', Rule::in(['single', 'final'])],
            'timer_mode' => ['sometimes', Rule::in(['countdown', 'countup', 'none'])],
            'timer_settings' => 'sometimes|nullable|json',
            'allow_repetition' => 'sometimes|boolean',
            'max_repetition_count' => 'sometimes|nullable|integer|min:0|max:10',
            'is_public' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'settings' => 'sometimes|nullable|json',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'test title',
            'description' => 'test description',
            'type' => 'test type',
            'difficulty' => 'difficulty level',
            'test_type' => 'test type',
            'timer_mode' => 'timer mode',
            'timer_settings' => 'timer settings',
            'allow_repetition' => 'allow repetition',
            'max_repetition_count' => 'maximum repetition count',
            'is_public' => 'public status',
            'is_published' => 'publication status',
            'settings' => 'test settings',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Test type must be one of: reading, listening, speaking, writing',
            'difficulty.in' => 'Difficulty must be one of: beginner, intermediate, advanced',
            'test_type.in' => 'Test type must be one of: single, final',
            'timer_mode.in' => 'Timer mode must be one of: countdown, countup, none',
            'max_repetition_count.max' => 'Maximum repetition count cannot exceed 10',
        ];
    }
}