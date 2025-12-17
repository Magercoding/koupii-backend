<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Models\ListeningTask;
use App\Models\ListeningAudioSegment;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListeningAudioSegmentController extends Controller
{
    public function __construct(
        private ListeningAudioService $audioService
    ) {}

    /**
     * Get audio segments for a listening task
     */
    public function index(ListeningTask $listeningTask): JsonResponse
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
    public function store(Request $request, ListeningTask $listeningTask): JsonResponse
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
     * Display a specific audio segment
     */
    public function show(ListeningAudioSegment $segment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $segment->id,
                'listening_task_id' => $segment->listening_task_id,
                'audio_url' => $segment->audio_url,
                'start_time' => $segment->start_time,
                'end_time' => $segment->end_time,
                'duration' => $segment->duration,
                'transcript' => $segment->transcript,
                'segment_type' => $segment->segment_type,
                'order' => $segment->order,
                'metadata' => $segment->metadata
            ]
        ]);
    }

    /**
     * Update audio segments
     */
    public function update(Request $request, ListeningTask $listeningTask): JsonResponse
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
     * Generate transcript for audio segments
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
     * Delete a specific audio segment
     */
    public function destroy(ListeningAudioSegment $segment): JsonResponse
    {
        try {
            $segment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Audio segment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete audio segment: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}