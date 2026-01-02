<?php

namespace App\Http\Resources\V1\Assignment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment' => new AssignmentResource([
                'assignment' => $this->resource['assignment'],
                'type' => $this->resource['type']
            ]),
            'statistics' => $this->resource['statistics'],
            'student_details' => $this->resource['student_details']->map(function($studentAssignment) {
                return [
                    'student_id' => $studentAssignment->student_id,
                    'student_name' => $studentAssignment->student->name ?? 'N/A',
                    'student_email' => $studentAssignment->student->email ?? 'N/A',
                    'status' => $studentAssignment->status,
                    'score' => $studentAssignment->score,
                    'attempt_count' => $studentAssignment->attempt_count,
                    'started_at' => $studentAssignment->started_at,
                    'completed_at' => $studentAssignment->completed_at,
                    'time_spent_seconds' => $studentAssignment->time_spent_seconds,
                    'time_spent_formatted' => $this->formatTime($studentAssignment->time_spent_seconds)
                ];
            })
        ];
    }

    private function formatTime(?int $seconds): ?string
    {
        if (!$seconds) return null;
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}