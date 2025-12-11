<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningAudioLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'audio_segment_id' => $this->audio_segment_id,
            'question_id' => $this->question_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration' => $this->duration,
            'played_at' => $this->played_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed fields
            'duration_formatted' => $this->getFormattedDuration(),
            'time_range' => $this->getTimeRange(),
            'play_percentage' => $this->getPlayPercentage(),
            
            // Include audio segment information when loaded
            'audio_segment' => $this->whenLoaded('audioSegment', function () {
                return [
                    'id' => $this->audioSegment->id,
                    'passage_id' => $this->audioSegment->passage_id,
                    'audio_url' => $this->audioSegment->audio_url,
                    'start_time' => $this->audioSegment->start_time,
                    'end_time' => $this->audioSegment->end_time,
                    'duration' => $this->audioSegment->duration,
                    'transcript' => $this->audioSegment->transcript,
                    'order' => $this->audioSegment->order
                ];
            }),
            
            // Include question information when loaded
            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_text' => $this->question->question_text,
                    'question_type' => $this->question->question_type,
                    'question_order' => $this->question->question_order
                ];
            })
        ];
    }
    
    /**
     * Get formatted duration (MM:SS)
     */
    private function getFormattedDuration(): string
    {
        $totalSeconds = (int) $this->duration;
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    /**
     * Get time range as formatted string
     */
    private function getTimeRange(): string
    {
        $startFormatted = $this->formatTime($this->start_time);
        $endFormatted = $this->formatTime($this->end_time);
        
        return "{$startFormatted} - {$endFormatted}";
    }
    
    /**
     * Calculate what percentage of the segment was played
     */
    private function getPlayPercentage(): float
    {
        if (!$this->audioSegment || $this->audioSegment->duration <= 0) {
            return 0;
        }
        
        return round(($this->duration / $this->audioSegment->duration) * 100, 1);
    }
    
    /**
     * Format time in MM:SS format
     */
    private function formatTime(float $seconds): string
    {
        $totalSeconds = (int) $seconds;
        $minutes = floor($totalSeconds / 60);
        $remainingSeconds = $totalSeconds % 60;
        
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}