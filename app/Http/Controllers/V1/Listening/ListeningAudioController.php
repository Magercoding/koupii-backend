<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\AudioUploadRequest;
use App\Http\Requests\V1\Listening\AudioProcessRequest;
use App\Http\Resources\V1\Listening\AudioResource;
use App\Models\ListeningTask;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
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
}