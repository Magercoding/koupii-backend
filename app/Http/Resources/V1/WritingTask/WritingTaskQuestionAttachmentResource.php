<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskQuestionAttachmentResource extends JsonResource
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
            'resource_name' => $this->resource_name,
            'resource_url' => $this->resource_url,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'description' => $this->description,
            'is_downloadable' => $this->is_downloadable,
            'display_order' => $this->display_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}