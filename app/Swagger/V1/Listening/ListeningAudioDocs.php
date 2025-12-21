<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningAudioDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/listening/submissions/{submission}/audio/play",
     *     summary="Log audio play event",
     *     tags={"Listening Audio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="audio_segment_id", type="string", format="uuid"),
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="start_time", type="number", format="float"),
     *             @OA\Property(property="end_time", type="number", format="float"),
     *             @OA\Property(property="duration", type="number", format="float"),
     *             @OA\Property(property="play_position", type="number", format="float"),
     *             @OA\Property(property="user_action", type="string", enum={"play", "pause", "seek", "replay", "stop"}),
     *             @OA\Property(property="device_info", type="object",
     *                 @OA\Property(property="volume", type="number", format="float", minimum=0, maximum=1),
     *                 @OA\Property(property="playback_rate", type="number", format="float", minimum=0.25, maximum=2.0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio play logged successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Audio play logged"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningAudioLogResource")
     *         )
     *     )
     * )
     */
    public function logAudioPlay() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{submission}/audio/logs",
     *     summary="Get audio play logs for submission",
     *     tags={"Listening Audio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="audio_segment_id",
     *         in="query",
     *         description="Filter by audio segment",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio logs retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ListeningAudioLogResource"))
     *         )
     *     )
     * )
     */
    public function getAudioLogs() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/audio/segments/{segment}",
     *     summary="Get audio segment details",
     *     tags={"Listening Audio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="segment",
     *         in="path",
     *         description="Audio segment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio segment details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="passage_id", type="string", format="uuid"),
     *                 @OA\Property(property="audio_url", type="string"),
     *                 @OA\Property(property="start_time", type="number", format="float"),
     *                 @OA\Property(property="end_time", type="number", format="float"),
     *                 @OA\Property(property="duration", type="number", format="float"),
     *                 @OA\Property(property="transcript", type="string"),
     *                 @OA\Property(property="order", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function getAudioSegment() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{submission}/audio/stats",
     *     summary="Get audio play statistics for submission",
     *     tags={"Listening Audio"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Audio statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/AudioPlayStatsResponse")
     *         )
     *     )
     * )
     */
    public function getAudioStats() {}

    /**
     * @OA\Schema(
     *     schema="ListeningAudioLogResource",
     *     type="object",
     *     title="Listening Audio Log Resource",
     *     description="Audio play log data",
     *     @OA\Property(property="id", type="string", format="uuid", description="Log ID"),
     *     @OA\Property(property="submission_id", type="string", format="uuid", description="Submission ID"),
     *     @OA\Property(property="audio_segment_id", type="string", format="uuid", description="Audio segment ID"),
     *     @OA\Property(property="question_id", type="string", format="uuid", description="Related question ID"),
     *     @OA\Property(property="start_time", type="number", format="float", description="Play start time in seconds"),
     *     @OA\Property(property="end_time", type="number", format="float", description="Play end time in seconds"),
     *     @OA\Property(property="duration", type="number", format="float", description="Play duration in seconds"),
     *     @OA\Property(property="played_at", type="string", format="date-time", description="When audio was played"),
     *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *     @OA\Property(property="duration_formatted", type="string", description="Formatted duration (MM:SS)"),
     *     @OA\Property(property="time_range", type="string", description="Time range as formatted string"),
     *     @OA\Property(property="play_percentage", type="number", format="float", description="Percentage of segment played"),
     *     @OA\Property(
     *         property="audio_segment",
     *         type="object",
     *         description="Audio segment information",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="passage_id", type="string", format="uuid"),
     *         @OA\Property(property="audio_url", type="string"),
     *         @OA\Property(property="start_time", type="number", format="float"),
     *         @OA\Property(property="end_time", type="number", format="float"),
     *         @OA\Property(property="duration", type="number", format="float"),
     *         @OA\Property(property="transcript", type="string"),
     *         @OA\Property(property="order", type="integer")
     *     ),
     *     @OA\Property(
     *         property="question",
     *         type="object",
     *         description="Related question information",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="question_text", type="string"),
     *         @OA\Property(property="question_type", type="string"),
     *         @OA\Property(property="question_order", type="integer")
     *     )
     * )
     */
    public function audioLogSchema() {}

    /**
     * @OA\Schema(
     *     schema="AudioPlayStatsResponse",
     *     type="object",
     *     title="Audio Play Statistics",
     *     description="Audio engagement statistics",
     *     @OA\Property(property="total_plays", type="integer", description="Total number of audio plays"),
     *     @OA\Property(property="total_listen_time", type="number", format="float", description="Total listening time in seconds"),
     *     @OA\Property(property="unique_segments_played", type="integer", description="Number of unique segments played"),
     *     @OA\Property(property="play_counts_by_segment", type="object", description="Play count for each segment"),
     *     @OA\Property(property="average_plays_per_segment", type="number", format="float", description="Average plays per segment"),
     *     @OA\Property(
     *         property="most_played_segment",
     *         type="object",
     *         description="Most replayed segment",
     *         @OA\Property(property="segment_id", type="string", format="uuid"),
     *         @OA\Property(property="play_count", type="integer"),
     *         @OA\Property(property="audio_url", type="string"),
     *         @OA\Property(property="duration", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="least_played_segment",
     *         type="object",
     *         description="Least replayed segment",
     *         @OA\Property(property="segment_id", type="string", format="uuid"),
     *         @OA\Property(property="play_count", type="integer"),
     *         @OA\Property(property="audio_url", type="string"),
     *         @OA\Property(property="duration", type="number", format="float")
     *     ),
     *     @OA\Property(property="play_distribution", type="object", description="Play distribution by hour"),
     *     @OA\Property(property="listen_time_by_segment", type="object", description="Total listen time per segment")
     * )
     */
    public function audioStatsSchema() {}
}

