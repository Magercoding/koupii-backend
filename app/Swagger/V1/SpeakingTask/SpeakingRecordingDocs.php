<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingRecordingDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/speaking/recordings/upload",
     *     summary="Upload speaking recording with speech-to-text processing",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="submission_id", type="string", format="uuid", description="Submission ID"),
     *                 @OA\Property(property="question_id", type="string", format="uuid", description="Question ID"),
     *                 @OA\Property(property="audio_file", type="string", format="binary", description="Audio file (mp3, wav, m4a, aac, ogg, webm)"),
     *                 @OA\Property(property="duration_seconds", type="integer", description="Recording duration in seconds")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recording uploaded and processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Recording uploaded successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingRecordingResource")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=413, description="File too large")
     * )
     */
    public function uploadRecording() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/recordings/{recording}",
     *     summary="Get recording details",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recording details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingRecordingResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recording not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function show() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/recordings/{recording}/download",
     *     summary="Download recording file",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="audio/*",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recording or file not found")
     * )
     */
    public function download() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/speaking/recordings/{recording}",
     *     summary="Delete recording",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recording deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Recording deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Cannot delete recording from submitted work")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/recordings/{recording}/process-speech",
     *     summary="Process speech-to-text for recording",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speech processing completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speech processing completed"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transcript", type="string", example="Hello, how are you today?"),
     *                 @OA\Property(property="confidence_score", type="number", format="float", example=0.95),
     *                 @OA\Property(property="fluency_score", type="number", format="float", example=87.5),
     *                 @OA\Property(property="speaking_rate", type="number", format="float", example=145.2),
     *                 @OA\Property(property="pause_analysis", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Recording already processed")
     * )
     */
    public function processSpeech() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/recordings/{recording}/transcript",
     *     summary="Get recording transcript",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transcript retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recording_id", type="string", format="uuid"),
     *                 @OA\Property(property="transcript", type="string", example="Hello, how are you today?"),
     *                 @OA\Property(property="confidence_score", type="number", format="float", example=0.95),
     *                 @OA\Property(property="processed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transcript not available")
     * )
     */
    public function getTranscript() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/recordings/{recording}/analysis",
     *     summary="Get speech analysis for recording",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="recording",
     *         in="path",
     *         description="Recording ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speech analysis retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recording_id", type="string", format="uuid"),
     *                 @OA\Property(property="duration_seconds", type="integer", example=45),
     *                 @OA\Property(property="transcript", type="string"),
     *                 @OA\Property(property="confidence_score", type="number", format="float", example=0.95),
     *                 @OA\Property(property="fluency_score", type="number", format="float", example=87.5),
     *                 @OA\Property(property="speaking_rate", type="number", format="float", example=145.2),
     *                 @OA\Property(property="pause_analysis", type="object"),
     *                 @OA\Property(property="word_count", type="integer", example=25),
     *                 @OA\Property(property="processed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Analysis not available")
     * )
     */
    public function getSpeechAnalysis() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/bulk/process-recordings",
     *     summary="Bulk process recordings for speech-to-text",
     *     tags={"Speaking Recordings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="recording_ids", type="array", @OA\Items(type="string", format="uuid"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk processing completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Processed 5 recordings successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="processed", type="integer", example=5),
     *                 @OA\Property(property="failed", type="integer", example=0),
     *                 @OA\Property(property="already_processed", type="integer", example=2),
     *                 @OA\Property(property="details", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function bulkProcessSpeech() {}
}