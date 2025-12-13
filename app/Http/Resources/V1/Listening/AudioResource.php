<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name,
            'file_url' => $this->file_url,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'duration' => $this->duration,
            'sample_rate' => $this->sample_rate,
            'channels' => $this->channels,
            'bit_rate' => $this->bit_rate,
            'format' => $this->format,
            'quality' => $this->quality,
            'language' => $this->language,
            'transcript' => $this->transcript,
            'transcript_confidence' => $this->transcript_confidence,
            'processing_status' => $this->processing_status,
            'processing_progress' => $this->processing_progress,
            'processing_error' => $this->processing_error,
            'waveform_data' => $this->waveform_data,
            'metadata' => $this->metadata,
            'segments_count' => $this->whenLoaded('segments', function () {
                return $this->segments->count();
            }),
            'segments' => $this->whenLoaded('segments', function () {
                return $this->segments->map(function ($segment) {
                    return [
                        'id' => $segment->id,
                        'start_time' => $segment->start_time,
                        'end_time' => $segment->end_time,
                        'duration' => $segment->duration,
                        'transcript' => $segment->transcript,
                        'label' => $segment->label,
                        'description' => $segment->description,
                        'is_key_segment' => $segment->is_key_segment,
                        'order' => $segment->order
                    ];
                });
            }),
            'upload_info' => [
                'uploaded_by' => $this->uploaded_by,
                'uploaded_at' => $this->created_at,
                'file_hash' => $this->file_hash
            ],
            'analysis' => [
                'silence_detection' => $this->silence_detection,
                'volume_analysis' => $this->volume_analysis,
                'frequency_analysis' => $this->frequency_analysis,
                'quality_score' => $this->quality_score
            ],
            'usage_stats' => [
                'play_count' => $this->play_count ?? 0,
                'download_count' => $this->download_count ?? 0,
                'last_played_at' => $this->last_played_at
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}