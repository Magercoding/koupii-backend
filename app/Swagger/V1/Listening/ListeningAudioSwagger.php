<?php

namespace App\Swagger\V1\Listening;

// ===== LISTENING AUDIO ENDPOINTS =====

/**
 * @OA\Post(
 *     path="/listening/audio/upload",
 *     tags={"Listening Audio"},
 *     summary="Upload audio file",
 *     description="Upload audio file for listening tasks",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="audio_file",
 *                     type="string",
 *                     format="binary",
 *                     description="Audio file (mp3, wav, m4a, aac, ogg)"
 *                 ),
 *                 @OA\Property(property="title", type="string", description="Audio title"),
 *                 @OA\Property(property="description", type="string", description="Audio description"),
 *                 @OA\Property(property="language", type="string", example="en", description="Audio language code"),
 *                 @OA\Property(property="auto_process", type="boolean", default=true, description="Auto-process after upload")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Audio uploaded successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="file_id", type="string", format="uuid"),
 *                         @OA\Property(property="file_url", type="string", format="uri"),
 *                         @OA\Property(property="file_size", type="integer", description="File size in bytes"),
 *                         @OA\Property(property="duration", type="number", format="float", description="Audio duration in seconds"),
 *                         @OA\Property(property="format", type="string", example="mp3"),
 *                         @OA\Property(property="sample_rate", type="integer", example=44100),
 *                         @OA\Property(property="channels", type="integer", example=2)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/audio/process",
 *     tags={"Listening Audio"},
 *     summary="Process audio file",
 *     description="Process audio file to generate transcript, segments, and metadata",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"file_id"},
 *             @OA\Property(property="file_id", type="string", format="uuid", description="Uploaded file ID"),
 *             @OA\Property(property="generate_transcript", type="boolean", default=true),
 *             @OA\Property(property="generate_segments", type="boolean", default=true),
 *             @OA\Property(property="language", type="string", example="en", description="Audio language"),
 *             @OA\Property(property="include_timestamps", type="boolean", default=true),
 *             @OA\Property(property="include_speaker_labels", type="boolean", default=false),
 *             @OA\Property(property="segment_duration", type="integer", default=30, description="Auto-segment duration in seconds")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audio processed successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="transcript", type="string"),
 *                         @OA\Property(property="segments", type="array", @OA\Items(ref="#/components/schemas/AudioSegment")),
 *                         @OA\Property(property="metadata", type="object"),
 *                         @OA\Property(property="processing_time", type="number", format="float"),
 *                         @OA\Property(property="confidence_score", type="number", format="float", minimum=0, maximum=1)
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/audio/tasks/{id}/details",
 *     tags={"Listening Audio"},
 *     summary="Get audio details",
 *     description="Get detailed audio information for a listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audio details retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="audio_url", type="string", format="uri"),
 *                         @OA\Property(property="duration", type="number", format="float"),
 *                         @OA\Property(property="format", type="string"),
 *                         @OA\Property(property="file_size", type="integer"),
 *                         @OA\Property(property="sample_rate", type="integer"),
 *                         @OA\Property(property="channels", type="integer"),
 *                         @OA\Property(property="transcript", type="string"),
 *                         @OA\Property(property="language", type="string"),
 *                         @OA\Property(property="quality_score", type="number", format="float")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/audio/tasks/{id}/segments",
 *     tags={"Listening Audio"},
 *     summary="Get audio segments",
 *     description="Get audio segments for a listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audio segments retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(ref="#/components/schemas/AudioSegment")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/audio/tasks/{id}/segments",
 *     tags={"Listening Audio"},
 *     summary="Create audio segments",
 *     description="Create new audio segments for a task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="segments",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="start_time", type="number", format="float", minimum=0),
 *                     @OA\Property(property="end_time", type="number", format="float"),
 *                     @OA\Property(property="label", type="string", maxLength=255),
 *                     @OA\Property(property="description", type="string"),
 *                     @OA\Property(property="is_key_segment", type="boolean", default=false)
 *                 )
 *             ),
 *             @OA\Property(property="auto_generate", type="boolean", default=false, description="Auto-generate segments if none provided")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Segments created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(ref="#/components/schemas/AudioSegment")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/audio/tasks/{id}/transcript/generate",
 *     tags={"Listening Audio"},
 *     summary="Generate transcript",
 *     description="Generate or regenerate transcript for audio",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="language", type="string", example="en", description="Audio language"),
 *             @OA\Property(property="include_timestamps", type="boolean", default=true),
 *             @OA\Property(property="include_speaker_labels", type="boolean", default=false),
 *             @OA\Property(property="confidence_threshold", type="number", format="float", minimum=0, maximum=1, default=0.8)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Transcript generated successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="transcript", type="string"),
 *                         @OA\Property(property="timestamps", type="array", @OA\Items(type="object")),
 *                         @OA\Property(property="speaker_labels", type="array", @OA\Items(type="object")),
 *                         @OA\Property(property="confidence_score", type="number", format="float"),
 *                         @OA\Property(property="processing_time", type="number", format="float")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/audio/submissions/{id}/logs",
 *     tags={"Listening Audio"},
 *     summary="Log audio play",
 *     description="Log audio play interaction for analytics",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="audio_segment_id", type="string", format="uuid", description="Audio segment ID (optional)"),
 *             @OA\Property(property="start_time", type="number", format="float", description="Play start time"),
 *             @OA\Property(property="end_time", type="number", format="float", description="Play end time"),
 *             @OA\Property(property="play_duration", type="number", format="float", description="Actual play duration"),
 *             @OA\Property(property="interaction_type", type="string", enum={"play", "pause", "seek", "replay"}, description="Type of interaction"),
 *             @OA\Property(property="timestamp", type="string", format="date-time", description="Interaction timestamp")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audio play logged successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/audio/submissions/{id}/stats",
 *     tags={"Listening Audio"},
 *     summary="Get audio interaction stats",
 *     description="Get audio interaction statistics for a submission",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audio stats retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="total_plays", type="integer"),
 *                         @OA\Property(property="total_play_time", type="number", format="float"),
 *                         @OA\Property(property="unique_segments_played", type="integer"),
 *                         @OA\Property(property="replay_count", type="integer"),
 *                         @OA\Property(property="average_play_duration", type="number", format="float"),
 *                         @OA\Property(property="completion_percentage", type="number", format="float"),
 *                         @OA\Property(
 *                             property="segment_stats",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="segment_id", type="string"),
 *                                 @OA\Property(property="plays", type="integer"),
 *                                 @OA\Property(property="total_time", type="number", format="float")
 *                             )
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/audio/tasks/{id}/waveform",
 *     tags={"Listening Audio"},
 *     summary="Get audio waveform",
 *     description="Get audio waveform data for visualization",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="resolution",
 *         in="query",
 *         description="Waveform resolution",
 *         @OA\Schema(type="integer", minimum=100, maximum=10000, default=1000)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Waveform data retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(
 *                             property="waveform",
 *                             type="array",
 *                             @OA\Items(type="number", format="float"),
 *                             description="Waveform amplitude data"
 *                         ),
 *                         @OA\Property(property="duration", type="number", format="float"),
 *                         @OA\Property(property="resolution", type="integer"),
 *                         @OA\Property(property="sample_rate", type="integer")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */