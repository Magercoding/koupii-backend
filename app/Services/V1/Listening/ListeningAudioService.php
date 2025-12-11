<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningAudioLog;
use App\Models\ListeningSubmission;
use App\Models\ListeningAudioSegment;
use Illuminate\Support\Facades\DB;

class ListeningAudioService
{
    /**
     * Log audio play event
     */
    public function logAudioPlay(ListeningSubmission $submission, array $data): ListeningAudioLog
    {
        return DB::transaction(function () use ($submission, $data) {
            // Create audio log entry
            $log = ListeningAudioLog::create([
                'submission_id' => $submission->id,
                'audio_segment_id' => $data['audio_segment_id'],
                'question_id' => $data['question_id'] ?? null,
                'start_time' => $data['start_time'] ?? 0,
                'end_time' => $data['end_time'] ?? 0,
                'duration' => $data['duration'] ?? 0,
                'played_at' => now()
            ]);

            // Update submission's audio play counts
            $this->updateSubmissionPlayCounts($submission, $data['audio_segment_id']);

            // Update question answer play count if question_id provided
            if (isset($data['question_id'])) {
                $this->updateQuestionPlayCount($submission, $data['question_id']);
            }

            return $log->load('audioSegment');
        });
    }

    /**
     * Update submission's audio play counts
     */
    private function updateSubmissionPlayCounts(ListeningSubmission $submission, string $segmentId): void
    {
        $playCounts = $submission->audio_play_counts ?? [];
        
        if (!isset($playCounts[$segmentId])) {
            $playCounts[$segmentId] = 0;
        }
        
        $playCounts[$segmentId]++;
        
        $submission->update(['audio_play_counts' => $playCounts]);
    }

    /**
     * Update question answer play count
     */
    private function updateQuestionPlayCount(ListeningSubmission $submission, string $questionId): void
    {
        $answer = $submission->answers()->where('question_id', $questionId)->first();
        
        if ($answer) {
            $answer->increment('play_count');
        }
    }

    /**
     * Get audio statistics for a submission
     */
    public function getAudioStats(ListeningSubmission $submission): array
    {
        $logs = $submission->audioLogs;
        $playCounts = $submission->audio_play_counts ?? [];

        $totalPlays = $logs->count();
        $totalListenTime = $logs->sum('duration');
        $uniqueSegments = count($playCounts);

        return [
            'total_plays' => $totalPlays,
            'total_listen_time' => round($totalListenTime, 2),
            'unique_segments_played' => $uniqueSegments,
            'play_counts_by_segment' => $playCounts,
            'average_plays_per_segment' => $uniqueSegments > 0 ? round($totalPlays / $uniqueSegments, 2) : 0,
            'most_played_segment' => $this->getMostPlayedSegment($playCounts),
            'least_played_segment' => $this->getLeastPlayedSegment($playCounts),
            'play_distribution' => $this->getPlayDistribution($logs),
            'listen_time_by_segment' => $this->getListenTimeBySegment($logs)
        ];
    }

    /**
     * Get the most played audio segment
     */
    private function getMostPlayedSegment(array $playCounts): ?array
    {
        if (empty($playCounts)) {
            return null;
        }

        $maxPlays = max($playCounts);
        $segmentId = array_search($maxPlays, $playCounts);

        $segment = ListeningAudioSegment::find($segmentId);

        return $segment ? [
            'segment_id' => $segmentId,
            'play_count' => $maxPlays,
            'audio_url' => $segment->audio_url,
            'duration' => $segment->duration
        ] : null;
    }

    /**
     * Get the least played audio segment
     */
    private function getLeastPlayedSegment(array $playCounts): ?array
    {
        if (empty($playCounts)) {
            return null;
        }

        $minPlays = min($playCounts);
        $segmentId = array_search($minPlays, $playCounts);

        $segment = ListeningAudioSegment::find($segmentId);

        return $segment ? [
            'segment_id' => $segmentId,
            'play_count' => $minPlays,
            'audio_url' => $segment->audio_url,
            'duration' => $segment->duration
        ] : null;
    }

    /**
     * Get play distribution over time
     */
    private function getPlayDistribution($logs): array
    {
        $distribution = [];
        
        foreach ($logs as $log) {
            $hour = $log->played_at->format('H');
            if (!isset($distribution[$hour])) {
                $distribution[$hour] = 0;
            }
            $distribution[$hour]++;
        }

        return $distribution;
    }

    /**
     * Get total listen time by segment
     */
    private function getListenTimeBySegment($logs): array
    {
        $listenTime = [];
        
        foreach ($logs as $log) {
            $segmentId = $log->audio_segment_id;
            if (!isset($listenTime[$segmentId])) {
                $listenTime[$segmentId] = 0;
            }
            $listenTime[$segmentId] += $log->duration;
        }

        return $listenTime;
    }

    /**
     * Get audio segment usage analytics
     */
    public function getSegmentAnalytics(string $segmentId): array
    {
        $segment = ListeningAudioSegment::findOrFail($segmentId);
        $logs = ListeningAudioLog::where('audio_segment_id', $segmentId)->get();

        return [
            'segment_info' => [
                'id' => $segment->id,
                'audio_url' => $segment->audio_url,
                'duration' => $segment->duration,
                'transcript' => $segment->transcript
            ],
            'usage_stats' => [
                'total_plays' => $logs->count(),
                'unique_users' => $logs->pluck('submission.student_id')->unique()->count(),
                'average_play_duration' => $logs->avg('duration'),
                'total_listen_time' => $logs->sum('duration')
            ],
            'play_patterns' => [
                'most_common_start_time' => $this->getMostCommonStartTime($logs),
                'average_completion_rate' => $this->getAverageCompletionRate($logs, $segment->duration),
                'peak_usage_hours' => $this->getPeakUsageHours($logs)
            ]
        ];
    }

    /**
     * Get most common start time for audio plays
     */
    private function getMostCommonStartTime($logs): float
    {
        $startTimes = $logs->pluck('start_time')->filter()->toArray();
        
        if (empty($startTimes)) {
            return 0;
        }

        // Group by ranges and find most common
        $ranges = [];
        foreach ($startTimes as $time) {
            $range = floor($time / 10) * 10; // Group by 10-second ranges
            $ranges[$range] = ($ranges[$range] ?? 0) + 1;
        }

        return array_search(max($ranges), $ranges);
    }

    /**
     * Calculate average completion rate
     */
    private function getAverageCompletionRate($logs, float $totalDuration): float
    {
        if ($totalDuration <= 0) {
            return 0;
        }

        $completionRates = $logs->map(function ($log) use ($totalDuration) {
            return ($log->duration / $totalDuration) * 100;
        });

        return round($completionRates->avg(), 2);
    }

    /**
     * Get peak usage hours
     */
    private function getPeakUsageHours($logs): array
    {
        $hourCounts = [];
        
        foreach ($logs as $log) {
            $hour = $log->played_at->format('H');
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }

        arsort($hourCounts);
        
        return array_slice($hourCounts, 0, 3, true);
    }

    /**
     * Clean up old audio logs (for maintenance)
     */
    public function cleanupOldLogs(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return ListeningAudioLog::where('played_at', '<', $cutoffDate)->delete();
    }

    /**
     * Export audio analytics data
     */
    public function exportAnalytics(ListeningSubmission $submission): array
    {
        $stats = $this->getAudioStats($submission);
        $logs = $submission->audioLogs()->with('audioSegment')->get();

        return [
            'submission_info' => [
                'id' => $submission->id,
                'student_id' => $submission->student_id,
                'test_id' => $submission->test_id,
                'status' => $submission->status,
                'started_at' => $submission->started_at,
                'submitted_at' => $submission->submitted_at
            ],
            'audio_statistics' => $stats,
            'detailed_logs' => $logs->map(function ($log) {
                return [
                    'played_at' => $log->played_at,
                    'audio_segment_id' => $log->audio_segment_id,
                    'audio_url' => $log->audioSegment->audio_url ?? null,
                    'start_time' => $log->start_time,
                    'end_time' => $log->end_time,
                    'duration' => $log->duration,
                    'question_id' => $log->question_id
                ];
            })
        ];
    }
}