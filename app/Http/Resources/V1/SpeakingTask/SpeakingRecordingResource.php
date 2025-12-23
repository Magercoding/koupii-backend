<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingRecordingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'question_id' => $this->question_id,
            
            // File information
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url ?? null,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_seconds 
                ? $this->formatTime($this->duration_seconds) 
                : null,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->file_size 
                ? $this->formatFileSize($this->file_size) 
                : null,

            // Speech analysis
            'transcript' => $this->transcript,
            'confidence_score' => $this->confidence_score,
            'fluency_score' => $this->fluency_score,
            'speaking_rate' => $this->speaking_rate,
            'pause_analysis' => $this->pause_analysis,
            'word_count' => $this->transcript ? str_word_count($this->transcript) : null,
            
            // Processing status
            'speech_processed' => $this->speech_processed,
            'speech_processed_at' => $this->speech_processed_at,

            // Question information (when loaded)
            'question' => $this->when(
                $this->relationLoaded('question'),
                [
                    'id' => $this->question?->id,
                    'topic' => $this->question?->topic,
                    'prompt' => $this->question?->prompt,
                    'preparation_time_seconds' => $this->question?->preparation_time_seconds,
                    'response_time_seconds' => $this->question?->response_time_seconds,
                ]
            ),

            // Submission context (when needed)
            'submission' => $this->when(
                $this->relationLoaded('submission'),
                [
                    'id' => $this->submission?->id,
                    'status' => $this->submission?->status,
                    'attempt_number' => $this->submission?->attempt_number,
                ]
            ),

            // Analysis summary
            'quality_indicators' => $this->when(
                $this->speech_processed,
                [
                    'confidence_level' => $this->getConfidenceLevel(),
                    'fluency_level' => $this->getFluencyLevel(),
                    'speaking_pace' => $this->getSpeakingPace(),
                    'has_long_pauses' => $this->hasLongPauses(),
                ]
            ),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getConfidenceLevel(): string
    {
        if (!$this->confidence_score) return 'unknown';
        
        if ($this->confidence_score >= 0.9) return 'very_high';
        if ($this->confidence_score >= 0.75) return 'high';
        if ($this->confidence_score >= 0.5) return 'medium';
        if ($this->confidence_score >= 0.25) return 'low';
        return 'very_low';
    }

    private function getFluencyLevel(): string
    {
        if (!$this->fluency_score) return 'unknown';
        
        if ($this->fluency_score >= 90) return 'excellent';
        if ($this->fluency_score >= 75) return 'good';
        if ($this->fluency_score >= 60) return 'fair';
        if ($this->fluency_score >= 40) return 'poor';
        return 'very_poor';
    }

    private function getSpeakingPace(): string
    {
        if (!$this->speaking_rate) return 'unknown';
        
        // Words per minute
        if ($this->speaking_rate >= 180) return 'very_fast';
        if ($this->speaking_rate >= 150) return 'fast';
        if ($this->speaking_rate >= 120) return 'normal';
        if ($this->speaking_rate >= 90) return 'slow';
        return 'very_slow';
    }

    private function hasLongPauses(): bool
    {
        if (!$this->pause_analysis) return false;
        
        $pauseData = is_string($this->pause_analysis) 
            ? json_decode($this->pause_analysis, true) 
            : $this->pause_analysis;
            
        if (!is_array($pauseData)) return false;
        
        // Check if there are pauses longer than 3 seconds
        $longPauses = collect($pauseData)->filter(function ($pause) {
            return isset($pause['duration']) && $pause['duration'] > 3.0;
        });
        
        return $longPauses->isNotEmpty();
    }
}