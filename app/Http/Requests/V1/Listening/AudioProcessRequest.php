<?php

namespace App\Http\Requests\V1\Listening;

use App\Http\Requests\BaseRequest;

class AudioProcessRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file_id' => 'required|string',
            'generate_transcript' => 'nullable|boolean',
            'generate_segments' => 'nullable|boolean',
            'language' => 'nullable|string|size:2',
            'include_timestamps' => 'nullable|boolean',
            'include_speaker_labels' => 'nullable|boolean',
            'segment_duration' => 'nullable|integer|min:5|max:300',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'processing_options' => 'nullable|array'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file_id.required' => 'File ID is required.',
            'language.size' => 'Language code must be exactly 2 characters.',
            'segment_duration.min' => 'Segment duration must be at least 5 seconds.',
            'segment_duration.max' => 'Segment duration cannot exceed 5 minutes.',
            'confidence_threshold.min' => 'Confidence threshold must be between 0 and 1.',
            'confidence_threshold.max' => 'Confidence threshold must be between 0 and 1.'
        ];
    }
}