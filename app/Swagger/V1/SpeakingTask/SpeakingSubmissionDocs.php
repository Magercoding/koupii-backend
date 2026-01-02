<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/speaking/submissions",
     *     tags={"Speaking Submissions"},
     *     summary="Get speaking submissions",
     *     description="Retrieve speaking submissions based on user role",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="test_id",
     *         in="query",
     *         required=false,
     *         description="Filter by test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"to_do", "in_progress", "submitted", "reviewed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="test_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_id", type="string", format="uuid"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="total_duration_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/tests/{test}/start",
     *     tags={"Speaking Submissions"},
     *     summary="Start speaking test",
     *     description="Start a new speaking test submission",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="test",
     *         in="path",
     *         required=true,
     *         description="Speaking test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="assignment_id", type="string", format="uuid", description="Assignment ID (optional)"),
     *                 @OA\Property(property="practice_mode", type="boolean", example=false, description="Whether this is a practice session")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking test started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking test started successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="in_progress"),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request")
     * )
     */
    public function start() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/submissions/{submission}/upload-recording",
     *     tags={"Speaking Submissions"},
     *     summary="Upload speaking recording",
     *     description="Upload audio recording for a specific question in the speaking test",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         required=true,
     *         description="Speaking submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"question_id", "audio_file"},
     *                 @OA\Property(property="question_id", type="string", format="uuid", description="Speaking question ID"),
     *                 @OA\Property(
     *                     property="audio_file", 
     *                     type="string", 
     *                     format="binary", 
     *                     description="Audio recording file (mp3, wav, m4a) - Max 50MB"
     *                 ),
     *                 @OA\Property(property="duration_seconds", type="integer", example=120, description="Recording duration in seconds"),
     *                 @OA\Property(property="recording_started_at", type="string", format="date-time", description="When recording started"),
     *                 @OA\Property(property="recording_ended_at", type="string", format="date-time", description="When recording ended")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recording uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Recording uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="recording_id", type="string", format="uuid"),
     *                 @OA\Property(property="file_path", type="string"),
     *                 @OA\Property(property="duration_seconds", type="integer"),
     *                 @OA\Property(property="file_size_bytes", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error - Invalid file format or size"),
     *     @OA\Response(response=403, description="Forbidden - Cannot update this submission")
     * )
     */
    public function uploadRecording() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/submissions/{submission}/submit",
     *     tags={"Speaking Submissions"},
     *     summary="Submit speaking test",
     *     description="Submit the completed speaking test for review",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         required=true,
     *         description="Speaking submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="final_notes", type="string", description="Any final notes or comments"),
     *                 @OA\Property(
     *                     property="additional_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Additional supporting files (optional)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking test submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking test submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="submitted"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                 @OA\Property(property="total_duration_seconds", type="integer"),
     *                 @OA\Property(property="recording_count", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Missing recordings or invalid state"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function submit() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/submissions/{submission}",
     *     tags={"Speaking Submissions"},
     *     summary="Get submission details",
     *     description="Get detailed speaking submission information including recordings",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         required=true,
     *         description="Speaking submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="score", type="integer", nullable=true),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(
     *                     property="recordings",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="question_id", type="string", format="uuid"),
     *                         @OA\Property(property="file_path", type="string"),
     *                         @OA\Property(property="duration_seconds", type="integer"),
     *                         @OA\Property(property="uploaded_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show() {}
}