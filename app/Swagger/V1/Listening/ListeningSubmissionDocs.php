<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions",
     *     tags={"Listening Submissions"},
     *     summary="Get listening submissions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/listening/tasks/{taskId}/submissions",
     *     tags={"Listening Submissions"},
     *     summary="Submit listening task",
     *     description="Submit answers and files for a listening task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Listening task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"answers"},
     *                 @OA\Property(
     *                     property="answers",
     *                     type="string",
     *                     description="JSON string of answers array",
     *                     example="[{""question_id"":""uuid"",""answer"":""A"",""selected_option"":""Option A""}]"
     *                 ),
     *                 @OA\Property(property="time_taken_seconds", type="integer", example=1800),
     *                 @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(
     *                     property="audio_play_counts",
     *                     type="string",
     *                     description="JSON object of audio play counts per segment",
     *                     example="{""segment_1"":3,""segment_2"":2}"
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Optional additional files (e.g., notes, recordings)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Submission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening submission created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized access"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{id}",
     *     tags={"Listening Submissions"},
     *     summary="Get submission details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found")
     * )
     */
    public function show() {}
}


