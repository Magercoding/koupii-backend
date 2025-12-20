<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningAudioLog;
use App\Models\ListeningSubmission;
use App\Models\ListeningAudioSegment;
use App\Models\ListeningTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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

    /**
     * Upload audio file for listening task
     */
    public function uploadAudio(ListeningTask $task, UploadedFile $file): array
    {
        try {
            $filename = 'listening-audio-' . $task->id . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('listening/audio', $filename, 'public');

            // Update task with audio file path
            $task->update([
                'audio_file' => $path,
                'audio_filename' => $file->getClientOriginalName(),
                'audio_size' => $file->getSize(),
                'audio_mime_type' => $file->getMimeType()
            ]);

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process audio file (convert format, extract metadata, etc.)
     */
    public function processAudio(array $data): array
    {
        try {
            $task = ListeningTask::findOrFail($data['task_id']);
            
            // Basic audio processing placeholder
            // In real implementation, you would:
            // - Convert audio to standard format (mp3, wav)
            // - Extract metadata (duration, bitrate, etc.)
            // - Generate waveform data
            // - Create audio segments
            
            $processedData = [
                'task_id' => $task->id,
                'processed_at' => now(),
                'format' => 'mp3',
                'status' => 'processed'
            ];

            // Update task with processed status
            $task->update([
                'audio_processed' => true,
                'audio_metadata' => $processedData
            ]);

            return [
                'success' => true,
                'data' => $processedData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get audio details for a listening task
     */
    public function getAudioDetails(ListeningTask $task): array
    {
        return [
            'task_id' => $task->id,
            'audio_file' => $task->audio_file,
            'audio_filename' => $task->audio_filename,
            'audio_size' => $task->audio_size,
            'audio_mime_type' => $task->audio_mime_type,
            'audio_url' => $task->audio_file ? Storage::disk('public')->url($task->audio_file) : null,
            'audio_processed' => $task->audio_processed,
            'audio_metadata' => $task->audio_metadata,
            'segments_count' => $task->audioSegments()->count(),
            'total_duration' => $this->calculateTotalDuration($task),
            'has_transcript' => $task->audioSegments()->whereNotNull('transcript')->exists()
        ];
    }

    /**
     * Get audio segments for a listening task
     */
    public function getAudioSegments(ListeningTask $task): array
    {
        $segments = $task->audioSegments()->orderBy('start_time')->get();

        return $segments->map(function ($segment) {
            return [
                'id' => $segment->id,
                'start_time' => $segment->start_time,
                'end_time' => $segment->end_time,
                'duration' => $segment->duration,
                'audio_url' => $segment->audio_url,
                'transcript' => $segment->transcript,
                'segment_type' => $segment->segment_type,
                'order' => $segment->order,
                'metadata' => $segment->metadata
            ];
        })->toArray();
    }

    /**
     * Create audio segments for a listening task
     */
    public function createSegments(ListeningTask $task, array $segments): array
    {
        $createdSegments = [];

        foreach ($segments as $segmentData) {
            $segment = ListeningAudioSegment::create([
                'id' => Str::uuid(),
                'listening_task_id' => $task->id,
                'start_time' => $segmentData['start_time'],
                'end_time' => $segmentData['end_time'],
                'duration' => $segmentData['duration'] ?? ($segmentData['end_time'] - $segmentData['start_time']),
                'audio_url' => $segmentData['audio_url'] ?? null,
                'transcript' => $segmentData['transcript'] ?? null,
                'segment_type' => $segmentData['segment_type'] ?? 'default',
                'order' => $segmentData['order'] ?? 1,
                'metadata' => $segmentData['metadata'] ?? null
            ]);

            $createdSegments[] = $segment;
        }

        return [
            'success' => true,
            'segments' => $createdSegments->map(function ($segment) {
                return [
                    'id' => $segment->id,
                    'start_time' => $segment->start_time,
                    'end_time' => $segment->end_time,
                    'duration' => $segment->duration,
                    'audio_url' => $segment->audio_url,
                    'transcript' => $segment->transcript
                ];
            })->toArray()
        ];
    }

    /**
     * Update audio segments for a listening task
     */
    public function updateSegments(ListeningTask $task, array $segments): array
    {
        $updatedSegments = [];

        foreach ($segments as $segmentData) {
            $segment = ListeningAudioSegment::where('listening_task_id', $task->id)
                ->where('id', $segmentData['id'])
                ->first();

            if ($segment) {
                $segment->update([
                    'start_time' => $segmentData['start_time'] ?? $segment->start_time,
                    'end_time' => $segmentData['end_time'] ?? $segment->end_time,
                    'duration' => $segmentData['duration'] ?? $segment->duration,
                    'audio_url' => $segmentData['audio_url'] ?? $segment->audio_url,
                    'transcript' => $segmentData['transcript'] ?? $segment->transcript,
                    'segment_type' => $segmentData['segment_type'] ?? $segment->segment_type,
                    'order' => $segmentData['order'] ?? $segment->order,
                    'metadata' => $segmentData['metadata'] ?? $segment->metadata
                ]);

                $updatedSegments[] = $segment;
            }
        }

        return [
            'success' => true,
            'segments' => collect($updatedSegments)->map(function ($segment) {
                return [
                    'id' => $segment->id,
                    'start_time' => $segment->start_time,
                    'end_time' => $segment->end_time,
                    'duration' => $segment->duration,
                    'audio_url' => $segment->audio_url,
                    'transcript' => $segment->transcript
                ];
            })->toArray()
        ];
    }

    /**
     * Generate transcript for a listening task
     */
    public function generateTranscript(ListeningTask $task, array $options = []): array
    {
        try {
            // Placeholder for actual transcript generation
            // In real implementation, you would:
            // - Use speech-to-text service (Google Speech API, AWS Transcribe, etc.)
            // - Process the audio file
            // - Generate timestamps for words/segments
            // - Save transcript to segments

            $segments = $task->audioSegments;
            $generatedTranscripts = [];

            foreach ($segments as $segment) {
                // Mock transcript generation
                $transcript = "This is a generated transcript for segment {$segment->id}";
                
                $segment->update(['transcript' => $transcript]);
                $generatedTranscripts[] = [
                    'segment_id' => $segment->id,
                    'transcript' => $transcript,
                    'confidence' => 0.95, // Mock confidence score
                    'words' => [] // Mock word-level timestamps
                ];
            }

            return [
                'success' => true,
                'transcripts' => $generatedTranscripts,
                'generated_at' => now()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get waveform data for a listening task
     */
    public function getWaveformData(ListeningTask $task): array
    {
        // Placeholder for waveform generation
        // In real implementation, you would:
        // - Analyze audio file
        // - Generate waveform peaks data
        // - Return amplitude data for visualization

        $mockWaveform = [];
        for ($i = 0; $i < 100; $i++) {
            $mockWaveform[] = rand(10, 100) / 100;
        }

        return [
            'task_id' => $task->id,
            'waveform_data' => $mockWaveform,
            'sample_rate' => 44100,
            'duration' => $this->calculateTotalDuration($task),
            'peaks_per_second' => 10
        ];
    }

    /**
     * Validate audio file
     */
    public function validateAudioFile(UploadedFile $file): array
    {
        $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/ogg'];
        $maxSize = 100 * 1024 * 1024; // 100MB
        $minDuration = 10; // 10 seconds minimum

        $errors = [];

        // Check file type
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid audio file format. Allowed formats: MP3, WAV, OGG';
        }

        // Check file size
        if ($file->getSize() > $maxSize) {
            $errors[] = 'Audio file too large. Maximum size: 100MB';
        }

        // Additional validation could include:
        // - Audio duration check
        // - Audio quality validation
        // - Virus scanning

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]
        ];
    }

    /**
     * Get metadata for a listening task's audio
     */
    public function getAudioMetadata(ListeningTask $task): array
    {
        return [
            'task_id' => $task->id,
            'file_info' => [
                'filename' => $task->audio_filename,
                'file_path' => $task->audio_file,
                'file_size' => $task->audio_size,
                'mime_type' => $task->audio_mime_type,
                'file_url' => $task->audio_file ? Storage::disk('public')->url($task->audio_file) : null
            ],
            'audio_properties' => $task->audio_metadata ?? [
                'duration' => $this->calculateTotalDuration($task),
                'bitrate' => null,
                'sample_rate' => null,
                'channels' => null,
                'format' => pathinfo($task->audio_filename ?? '', PATHINFO_EXTENSION)
            ],
            'processing_info' => [
                'processed' => $task->audio_processed ?? false,
                'segments_created' => $task->audioSegments()->exists(),
                'transcript_available' => $task->audioSegments()->whereNotNull('transcript')->exists(),
                'waveform_generated' => false // Could be a flag in database
            ],
            'statistics' => [
                'total_segments' => $task->audioSegments()->count(),
                'total_duration' => $this->calculateTotalDuration($task),
                'average_segment_duration' => $this->getAverageSegmentDuration($task)
            ]
        ];
    }

    /**
     * Calculate total duration of all audio segments
     */
    private function calculateTotalDuration(ListeningTask $task): float
    {
        return $task->audioSegments()->sum('duration') ?? 0;
    }

    /**
     * Get average segment duration
     */
    private function getAverageSegmentDuration(ListeningTask $task): float
    {
        $count = $task->audioSegments()->count();
        return $count > 0 ? $this->calculateTotalDuration($task) / $count : 0;
    }
}