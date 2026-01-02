<?php

namespace App\Http\Requests\V1\Assignment;

use App\Http\Requests\BaseRequest;

class UpdateAssignmentRequest extends BaseRequest
{

    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'due_date' => 'sometimes|date|after:now',
            'max_attempts' => 'sometimes|integer|min:1|max:10',
            'instructions' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive,archived'
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after' => 'Due date must be in the future',
            'max_attempts.integer' => 'Max attempts must be a number',
            'max_attempts.min' => 'Max attempts must be at least 1',
            'max_attempts.max' => 'Max attempts cannot exceed 10',
            'status.in' => 'Status must be one of: active, inactive, archived'
        ];
    }
}