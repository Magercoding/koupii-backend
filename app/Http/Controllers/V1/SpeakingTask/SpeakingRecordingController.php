<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SpeakingTask\RecordingUploadRequest;
use App\Http\Resources\V1\SpeakingTask\SpeakingRecordingResource;
use App\Models\SpeakingRecording;
use App\Models\SpeakingSubmission;
use App\Services\V1\SpeakingTask\SpeechToTextService;
use App\Services\V1\SpeakingTask\SpeakingSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpeakingRecordingController extends Controller
{
    public function __construct(
        private SpeechToTextService $speechToTextService,
        private SpeakingSubmissionService $submissionService
    ) {}

    /**
     * Upload a speaking recording
     */
    public function uploadRecording(RecordingUploadRequest $request): JsonResponse
    {
        try {
            $recording = $this->submissionService->uploadRecording(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Recording uploaded successfully',
                'data' => new SpeakingRecordingResource($recording)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload recording: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recording details
     */
    public function show(SpeakingRecording $recording): JsonResponse
    {
        // Check if user has access to this recording
        if (!$this->userCanAccessRecording($recording)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new SpeakingRecordingResource($recording)
        ]);
    }

    /**
     * Stream recording audio file (proxies from R2 storage)
     */
    public function stream(string $recordingId): \Illuminate\Http\Response|JsonResponse
    {
        $recording = \App\Models\SpeakingRecording::find($recordingId);

        if (!$recording) {
            return response()->json(['success' => false, 'message' => 'Recording not found'], 404);
        }

        $path = $recording->audio_file_path;

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'No audio file path on record'], 404);
        }

        try {
            if (!Storage::disk('speaking_recordings')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'File not found in storage: ' . $path], 404);
            }

            $contents = Storage::disk('speaking_recordings')->get($path);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'path' => $path], 500);
        }

        $ext = strtolower(preg_replace('/;.*$/', '', pathinfo($path, PATHINFO_EXTENSION)));
        $mimeMap = [
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'm4a'  => 'audio/mp4',
            'aac'  => 'audio/aac',
            'ogg'  => 'audio/ogg',
            'webm' => 'audio/webm',
        ];
        $mimeType = $mimeMap[$ext] ?? 'audio/webm';

        return response($contents, 200, [
            'Content-Type'        => $mimeType,
            'Content-Length'      => strlen($contents),
            'Accept-Ranges'       => 'bytes',
            'Cache-Control'       => 'no-cache',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Download recording file
     */
    public function download(SpeakingRecording $recording): BinaryFileResponse|JsonResponse
    {
        // Check if user has access to this recording
        if (!$this->userCanAccessRecording($recording)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!Storage::disk('speaking_recordings')->exists($recording->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return Storage::disk('speaking_recordings')->download(
            $recording->file_path,
            $recording->file_name
        );
    }

    /**
     * Delete a recording
     */
    public function destroy(SpeakingRecording $recording): JsonResponse
    {
        // Check if user has access to this recording
        if (!$this->userCanAccessRecording($recording)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Only allow deletion if submission is still in progress
        if ($recording->submission->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete recording from submitted work'
            ], 422);
        }

        try {
            // Delete file from storage
            if (Storage::disk('speaking_recordings')->exists($recording->file_path)) {
                Storage::disk('speaking_recordings')->delete($recording->file_path);
            }

            // Delete database record
            $recording->delete();

            return response()->json([
                'success' => true,
                'message' => 'Recording deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recording: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process speech-to-text for a recording
     */
    public function processSpeech(SpeakingRecording $recording): JsonResponse
    {
        // Check if user has access to this recording (teachers can process any recording)
        $user = Auth::user();
        if (!$this->userCanAccessRecording($recording) && 
            !$user->hasRole(['admin', 'teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($recording->speech_processed) {
            return response()->json([
                'success' => false,
                'message' => 'Recording already processed'
            ], 422);
        }

        try {
            $result = $this->speechToTextService->processRecording($recording);

            return response()->json([
                'success' => true,
                'message' => 'Speech processing completed',
                'data' => [
                    'transcript' => $result['transcript'],
                    'confidence_score' => $result['confidence_score'],
                    'fluency_score' => $result['fluency_score'],
                    'speaking_rate' => $result['speaking_rate'],
                    'pause_analysis' => $result['pause_analysis']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Speech processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transcript for a recording
     */
    public function getTranscript(SpeakingRecording $recording): JsonResponse
    {
        // Check if user has access to this recording
        if (!$this->userCanAccessRecording($recording)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$recording->transcript) {
            return response()->json([
                'success' => false,
                'message' => 'Transcript not available. Please process speech first.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recording_id' => $recording->id,
                'transcript' => $recording->transcript,
                'confidence_score' => $recording->confidence_score,
                'processed_at' => $recording->speech_processed_at
            ]
        ]);
    }

    /**
     * Get speech analysis for a recording
     */
    public function getSpeechAnalysis(SpeakingRecording $recording): JsonResponse
    {
        // Check if user has access to this recording
        if (!$this->userCanAccessRecording($recording)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$recording->speech_processed) {
            return response()->json([
                'success' => false,
                'message' => 'Speech analysis not available. Please process speech first.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recording_id' => $recording->id,
                'duration_seconds' => $recording->duration_seconds,
                'transcript' => $recording->transcript,
                'confidence_score' => $recording->confidence_score,
                'fluency_score' => $recording->fluency_score,
                'speaking_rate' => $recording->speaking_rate,
                'pause_analysis' => $recording->pause_analysis,
                'word_count' => $recording->transcript ? str_word_count($recording->transcript) : 0,
                'processed_at' => $recording->speech_processed_at
            ]
        ]);
    }

    /**
     * Bulk process recordings
     */
    public function bulkProcessSpeech(Request $request): JsonResponse
    {
        $request->validate([
            'recording_ids' => 'required|array',
            'recording_ids.*' => 'uuid|exists:speaking_recordings,id'
        ]);

        $user = Auth::user();
        if (!$user->hasRole(['admin', 'teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $recordings = SpeakingRecording::whereIn('id', $request->recording_ids)
            ->where('speech_processed', false)
            ->get();

        $results = [
            'processed' => 0,
            'failed' => 0,
            'already_processed' => 0,
            'details' => []
        ];

        foreach ($recordings as $recording) {
            try {
                $result = $this->speechToTextService->processRecording($recording);
                $results['processed']++;
                $results['details'][] = [
                    'recording_id' => $recording->id,
                    'status' => 'success',
                    'transcript_length' => strlen($result['transcript'])
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'recording_id' => $recording->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Processed {$results['processed']} recordings successfully",
            'data' => $results
        ]);
    }

    /**
     * Check if current user can access a recording
     */
    private function userCanAccessRecording(SpeakingRecording $recording): bool
    {
        $user = Auth::user();
        
        // Admins and teachers can access all recordings
        if ($user->hasRole(['admin', 'teacher'])) {
            return true;
        }
        
        // Students can only access their own recordings
        return $recording->submission->student_id === $user->id;
    }
}