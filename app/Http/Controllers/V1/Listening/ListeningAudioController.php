<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\AudioUploadRequest;
use App\Http\Requests\V1\Listening\AudioProcessRequest;
use App\Http\Requests\V1\Listening\LogAudioPlayRequest;
use App\Http\Resources\V1\Listening\AudioResource;
use App\Http\Resources\V1\Listening\ListeningAudioLogResource;
use App\Models\ListeningTask;
use App\Models\ListeningSubmission;
use App\Models\ListeningAudioSegment;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningAudioController extends Controller
{
    public function __construct(
        private ListeningAudioService $audioService
    ) {}

    /**
     * Upload audio file for listening task
     */
    public function uploadAudio(AudioUploadRequest $request): JsonResponse
    {
        try {
            $audio = $this->audioService->uploadAudio(
                $request->file('audio_file'),
                $request->validated()
            );

            return response()->json([
                'status' => 'success',
                'data' => new AudioResource($audio),
                'message' => 'Audio uploaded successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload audio: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process audio file (generate transcript, segments, etc.)
     */
    public function processAudio(AudioProcessRequest $request): JsonResponse
    {
        try {
            $result = $this->audioService->processAudio($request->validated());

            return response()->json([
                'status' => 'success',
                'data' => $result,
                'message' => 'Audio processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process audio: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get audio details for a listening task
     */
    public function getAudioDetails(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $audioDetails = $this->audioService->getAudioDetails($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => $audioDetails,
                'message' => 'Audio details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio details: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get audio segments for a listening task
     */
    public function getAudioSegments(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $segments = $this->audioService->getAudioSegments($listeningTask);

            return response()->json([
                'status' => 'success',
                'data' => $segments,
                'message' => 'Audio segments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio segments: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create audio segments
     */
    public function createSegments(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate([
                'segments' => 'required|array',
                'segments.*.start_time' => 'required|numeric|min:0',
                'segments.*.end_time' => 'required|numeric|gt:segments.*.start_time',
                'segments.*.label' => 'nullable|string|max:255',
                'segments.*.description' => 'nullable|string',
                'segments.*.is_key_segment' => 'boolean',
                'auto_generate' => 'boolean'
            ]);

            $segments = $this->audioService->createSegments(
                $listeningTask,
                $request->validated()
            );

            return response()->json([
                'status' => 'success',
                'data' => $segments,
                'message' => 'Audio segments created successfully'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create audio segments: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update audio segments
     */
    public function updateSegments(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate([
                'segments' => 'required|array',
                'segments.*.id' => 'nullable|string|exists:listening_audio_segments,id',
                'segments.*.start_time' => 'required|numeric|min:0',
                'segments.*.end_time' => 'required|numeric|gt:segments.*.start_time',
                'segments.*.label' => 'nullable|string|max:255',
                'segments.*.description' => 'nullable|string',
                'segments.*.is_key_segment' => 'boolean'
            ]);

            $segments = $this->audioService->updateSegments(
                $listeningTask,
                $request->get('segments')
            );

            return response()->json([
                'status' => 'success',
                'data' => $segments,
                'message' => 'Audio segments updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update audio segments: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate transcript for audio
     */
    public function generateTranscript(Request $request, ListeningTask $listeningTask): JsonResponse
    {
        try {
            $request->validate([
                'language' => 'nullable|string|size:2',
                'include_timestamps' => 'boolean',
                'include_speaker_labels' => 'boolean',
                'confidence_threshold' => 'nullable|numeric|min:0|max:1'
            ]);

            $transcript = $this->audioService->generateTranscript(
                $listeningTask,
                $request->validated()
            );

            return response()->json([
                'status' => 'success',
                'data' => $transcript,
                'message' => 'Transcript generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate transcript: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Log audio play interaction
     */
    public function logPlay(LogAudioPlayRequest $request, ListeningSubmission $submission): JsonResponse
    {
        try {
            $log = $this->audioService->logAudioPlay($submission, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Audio play logged successfully',
                'data' => new ListeningAudioLogResource($log)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get audio play logs
     */
    public function getLogs(ListeningSubmission $submission): JsonResponse
    {
        $logs = $submission->audioLogs()
            ->with(['audioSegment'])
            ->orderBy('played_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ListeningAudioLogResource::collection($logs)
        ]);
    }

    /**
     * Get specific audio segment
     */
    public function getSegment(ListeningAudioSegment $segment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $segment->id,
                'passage_id' => $segment->passage_id,
                'audio_url' => $segment->audio_url,
                'start_time' => $segment->start_time,
                'end_time' => $segment->end_time,
                'duration' => $segment->duration,
                'transcript' => $segment->transcript,
                'order' => $segment->order
            ]
        ]);
    }

    /**
     * Get audio interaction statistics
     */
    public function getStats(ListeningSubmission $submission): JsonResponse
    {
        $stats = $this->audioService->getAudioStats($submission);

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
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
     * Get audio metadata
     */
    public function getAudioMetadata(ListeningTask $listeningTask): JsonResponse
    {
        try {
            $metadata = $this->audioService->
            getAudioMetadata($listeningTask);

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
}