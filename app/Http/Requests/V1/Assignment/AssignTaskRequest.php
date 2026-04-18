<?php

namespace App\Http\Requests\V1\Assignment;

use App\Http\Requests\BaseRequest;

class AssignTaskRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => 'required|in:test,task',
            'test_id' => 'required_if:source_type,test|uuid',
            'task_id' => 'required_if:source_type,task|uuid',
            'task_type' => 'required_if:source_type,task|in:writing_task,reading_task,listening_task,speaking_task',
            'class_id' => 'required|uuid',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date|after:now',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'instructions' => 'nullable|string',
            'is_published' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }

    public function messages(): array
    {
        return [
            'source_type.required' => 'Source type is required (test or task)',
            'source_type.in' => 'Source type must be either: test, task',
            'test_id.required_if' => 'Test ID is required when assigning a test',
            'test_id.uuid' => 'Test ID must be a valid UUID',
            'task_id.required_if' => 'Task ID is required when assigning a task',
            'task_id.uuid' => 'Task ID must be a valid UUID',
            'task_type.required_if' => 'Task type is required when assigning a task',
            'task_type.in' => 'Task type must be one of: writing_task, reading_task, listening_task, speaking_task',
            'class_id.required' => 'Class ID is required',
            'class_id.uuid' => 'Class ID must be a valid UUID',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after' => 'Due date must be in the future',
            'max_attempts.integer' => 'Max attempts must be a number',
            'max_attempts.min' => 'Max attempts must be at least 1',
            'max_attempts.max' => 'Max attempts cannot exceed 10',
        ];
    }
}