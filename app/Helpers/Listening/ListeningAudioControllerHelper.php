<?php

namespace App\Helpers\Listening;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ListeningAudioControllerHelper
{
    /**
     * Standard success response
     */
    public static function successResponse($data, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'message' => $message
        ], $status);
    }

    /**
     * Standard error response
     */
    public static function errorResponse(string $message, \Exception $e, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message . ': ' . $e->getMessage()
        ], $status);
    }

    /**
     * Validation rules for audio segments
     */
    public static function getSegmentValidationRules(): array
    {
        return [
            'segments' => 'required|array|min:1|max:50',
            'segments.*.start_time' => 'required|numeric|min:0',
            'segments.*.end_time' => 'required|numeric|gt:segments.*.start_time',
            'segments.*.label' => 'nullable|string|max:255',
            'segments.*.description' => 'nullable|string|max:1000',
            'segments.*.is_key_segment' => 'boolean',
            'segments.*.transcript' => 'nullable|string',
            'auto_generate' => 'boolean'
        ];
    }

    /**
     * Validation rules for transcript generation
     */
    public static function getTranscriptValidationRules(): array
    {
        return [
            'language' => 'nullable|string|size:2|in:en,es,fr,de,it,pt,ru,ja,ko,zh',
            'include_timestamps' => 'boolean',
            'include_speaker_labels' => 'boolean',
            'confidence_threshold' => 'nullable|numeric|min:0|max:1',
            'auto_punctuation' => 'boolean',
            'filter_profanity' => 'boolean'
        ];
    }

    /**
     * Validation rules for audio file upload
     */
    public static function getAudioValidationRules(): array
    {
        return [
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac,ogg,flac|max:102400', // 100MB max
            'quality' => 'nullable|in:low,medium,high',
            'auto_enhance' => 'boolean',
            'noise_reduction' => 'boolean'
        ];
    }

    /**
     * Format segment data for response
     */
    public static function formatSegmentData(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'],
                'start_time' => round($segment['start_time'], 2),
                'end_time' => round($segment['end_time'], 2),
                'duration' => round($segment['duration'], 2),
                'audio_url' => $segment['audio_url'] ?? null,
                'transcript' => $segment['transcript'] ?? null,
                'segment_type' => $segment['segment_type'] ?? 'default',
                'order' => $segment['order'] ?? 1,
                'has_transcript' => !empty($segment['transcript']),
                'metadata' => $segment['metadata'] ?? []
            ];
        }, $segments);
    }

    /**
     * Calculate total audio duration from segments
     */
    public static function calculateTotalDuration(array $segments): float
    {
        return array_sum(array_column($segments, 'duration'));
    }

    /**
     * Check if all segments have transcripts
     */
    public static function hasCompleteTranscripts(array $segments): bool
    {
        foreach ($segments as $segment) {
            if (empty($segment['transcript'])) {
                return false;
            }
        }
        return true;
    }
}