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
            'task_id' => 'required|uuid',
            'task_type' => 'required|in:writing_task,reading_task,listening_task,speaking_task',
            'class_id' => 'required|uuid',
            'due_date' => 'required|date|after:now',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'instructions' => 'nullable|string',
            'auto_grade' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'task_id.required' => 'Task ID is required',
            'task_id.uuid' => 'Task ID must be a valid UUID',
            'task_type.required' => 'Task type is required',
            'task_type.in' => 'Task type must be one of: writing_task, reading_task, listening_task, speaking_task',
            'class_id.required' => 'Class ID is required',
            'class_id.uuid' => 'Class ID must be a valid UUID',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after' => 'Due date must be in the future',
            'max_attempts.integer' => 'Max attempts must be a number',
            'max_attempts.min' => 'Max attempts must be at least 1',
            'max_attempts.max' => 'Max attempts cannot exceed 10',
            'auto_grade.boolean' => 'Auto grade must be true or false'
        ];
    }
}