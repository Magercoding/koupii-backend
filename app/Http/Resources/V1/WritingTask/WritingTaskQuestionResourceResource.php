<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskQuestionResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'writing_question_id' => $this->writing_question_id,
            'resource_type' => $this->resource_type,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_url' => $this->file_path ? asset('storage/' . $this->file_path) : null,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->file_size ? $this->formatFileSize($this->file_size) : null,
            'description' => $this->description,
            'display_order' => $this->display_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format file size in human-readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}