<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Models\ListeningTask;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningAudioMetadataController extends Controller
{
    public function __construct(
        private ListeningAudioService $audioService
    ) {}

    /**
     * Get audio metadata
     */
    public function getMetadata(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $metadata = $this->audioService->getAudioMetadata($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => $metadata,
                'message' => 'Audio metadata retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio metadata: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get audio waveform data
     */
    public function getWaveform(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $waveform = $this->audioService->getWaveformData($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => $waveform,
                'message' => 'Waveform data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve waveform data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate audio file
     */
    public function validateAudio(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac,ogg|max:51200' // 50MB max
            ]);

            $validation = $this->audioService->validateAudioFile($request->file('audio_file'));

            return response()->json([
                'status' => 'success',
                'data' => $validation,
                'message' => 'Audio file validated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audio validation failed: ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get comprehensive audio information
     */
    public function getAudioInfo(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $metadata = $this->audioService->getAudioMetadata($listeningTask);
            $waveform = $this->audioService->getWaveformData($listeningTask);
            $segments = $this->audioService->getAudioSegments($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'metadata' => $metadata,
                    'waveform' => $waveform,
                    'segments_summary' => [
                        'count' => count($segments),
                        'total_duration' => array_sum(array_column($segments, 'duration')),
                        'has_transcripts' => count(array_filter($segments, fn($s) => !empty($s['transcript']))) > 0
                    ]
                ],
                'message' => 'Audio information retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio information: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}