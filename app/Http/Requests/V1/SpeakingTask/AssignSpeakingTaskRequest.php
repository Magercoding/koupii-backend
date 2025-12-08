<?php

namespace App\Http\Requests\V1\SpeakingTask;

use App\Http\Requests\BaseRequest;

class AssignSpeakingTaskRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:classes,id',
            'due_date' => 'nullable|date|after:now',
            'allow_retake' => 'boolean',
            'max_attempts' => 'integer|min:1|max:10',
        ];
    }
}
