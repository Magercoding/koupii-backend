<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingTeacherDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'total_time_seconds' => $this->total_time_seconds,
            'total_time_formatted' => $this->total_time_seconds 
                ? $this->formatTime($this->total_time_seconds) 
                : null,

            // Student information
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
            ],

            // Assignment and test information
            'assignment' => [
                'id' => $this->assignment->id,
                'due_date' => $this->assignment->due_date,
                'test' => [
                    'id' => $this->assignment->test->id,
                    'title' => $this->assignment->test->title,
                    'difficulty' => $this->assignment->test->difficulty,
                ],
                'class' => [
                    'id' => $this->assignment->class->id,
                    'name' => $this->assignment->class->name,
                ],
            ],

            // Speech analysis summary
            'speech_summary' => [
                'total_recordings' => $this->recordings->count(),
                'total_speaking_time' => $this->recordings->sum('duration_seconds'),
                'total_speaking_time_formatted' => $this->formatTime($this->recordings->sum('duration_seconds')),
                'average_confidence' => round($this->recordings->avg('confidence_score'), 2),
                'average_fluency' => round($this->recordings->avg('fluency_score'), 2),
                'average_speaking_rate' => round($this->recordings->avg('speaking_rate'), 2),
                'total_words' => $this->getTotalWordCount(),
                'has_transcripts' => $this->recordings->where('transcript', '!=', null)->isNotEmpty(),
            ],

            // Review status
            'review_status' => [
                'is_reviewed' => $this->status === 'reviewed',
                'needs_review' => $this->status === 'submitted',
                'review_score' => $this->review?->overall_score ?? null,
                'reviewed_at' => $this->review?->reviewed_at ?? null,
                'reviewed_by' => $this->review?->reviewedBy ? [
                    'id' => $this->review->reviewedBy->id,
                    'name' => $this->review->reviewedBy->name,
                ] : null,
            ],

            // Time information for sorting/filtering
            'submission_age_hours' => $this->submitted_at 
                ? round($this->submitted_at->diffInHours(now()), 1)
                : null,
            'is_overdue' => $this->assignment->due_date && 
                          $this->submitted_at && 
                          $this->submitted_at->gt($this->assignment->due_date),
            
            // Priority indicators
            'priority' => $this->calculatePriority(),
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

    private function getTotalWordCount(): int
    {
        return $this->recordings->sum(function ($recording) {
            return $recording->transcript 
                ? str_word_count($recording->transcript) 
                : 0;
        });
    }

    private function calculatePriority(): string
    {
        // Calculate priority based on submission age and due date
        if (!$this->submitted_at) {
            return 'low';
        }

        $hoursWaiting = $this->submitted_at->diffInHours(now());
        
        if ($this->assignment->due_date && $this->submitted_at->gt($this->assignment->due_date)) {
            return 'high'; // Overdue submission
        }

        if ($hoursWaiting > 48) {
            return 'high'; // Waiting more than 48 hours
        } elseif ($hoursWaiting > 24) {
            return 'medium'; // Waiting more than 24 hours
        } else {
            return 'low'; // Recent submission
        }
    }
}