<?php

namespace App\Http\Requests\V1\WritingTask;

use App\Http\Requests\BaseRequest;
use App\Models\WritingTask;
use Illuminate\Foundation\Http\FormRequest;

class SubmitWritingRequest extends BaseRequest
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
            'content' => 'required|string|min:10',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max per file
            'time_taken_seconds' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Writing content is required',
            'content.min' => 'Writing must be at least 10 characters long',
            'files.max' => 'Maximum 5 files allowed',
            'files.*.mimes' => 'File must be PDF, DOC, DOCX, or TXT format',
            'files.*.max' => 'File size cannot exceed 10MB',
            'time_taken_seconds.min' => 'Time taken cannot be negative',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $task = $this->route('task') ?? $this->route('id');
            if ($task && is_string($task)) {
                $writingTask = WritingTask::find($task);
                if ($writingTask && $writingTask->word_limit) {
                    $wordCount = str_word_count(strip_tags($this->content));
                    if ($wordCount > $writingTask->word_limit) {
                        $validator->errors()->add('content', "Content exceeds word limit of {$writingTask->word_limit} words. Current: {$wordCount} words.");
                    }
                }
            }
        });
    }
}
