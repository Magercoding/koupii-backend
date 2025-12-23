<?php

namespace App\Swagger\V1\SpeakingTask;

// ===== SPEAKING SUBMISSION ENDPOINTS =====

/**
 * @OA\Post(
 *     path="/speaking/submissions",
 *     tags={"Speaking Submissions"},
 *     summary="Start a new speaking submission",
 *     description="Create a new speaking submission for a task assignment",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"assignment_id"},
 *             @OA\Property(property="assignment_id", type="string", format="uuid", description="Task assignment UUID"),
 *             @OA\Property(property="attempt_number", type="integer", minimum=1, maximum=10, description="Attempt number"),
 *             @OA\Property(
 *                 property="device_info",
 *                 type="object",
 *                 @OA\Property(property="browser", type="string", maxLength=255, example="Chrome 120.0"),
 *                 @OA\Property(property="os", type="string", maxLength=255, example="Windows 11"),
 *                 @OA\Property(property="microphone", type="string", maxLength=255, example="Built-in Microphone"),
 *                 @OA\Property(property="audio_quality", type="string", enum={"high", "medium", "low"}, example="high")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Submission started successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingSubmissionResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized to access assignment",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/speaking/submissions/{id}",
 *     tags={"Speaking Submissions"},
 *     summary="Get speaking submission details",
 *     description="Retrieve detailed information about a specific speaking submission",
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
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingSubmissionResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Submission not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/speaking/submissions/{id}/answers",
 *     tags={"Speaking Submissions"},
 *     summary="Submit answer for a question",
 *     description="Submit an audio recording and response for a specific question",
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
 *             required={"question_id", "audio_url", "duration_seconds"},
 *             @OA\Property(property="question_id", type="string", format="uuid", description="Question UUID"),
 *             @OA\Property(property="audio_url", type="string", format="uri", description="Uploaded audio file URL"),
 *             @OA\Property(property="transcript", type="string", maxLength=5000, description="Speech transcript"),
 *             @OA\Property(property="duration_seconds", type="number", minimum=1, maximum=1800, description="Recording duration"),
 *             @OA\Property(property="confidence_score", type="number", minimum=0, maximum=1, description="Recognition confidence"),
 *             @OA\Property(property="preparation_time_used", type="integer", minimum=0, maximum=300, description="Preparation time used"),
 *             @OA\Property(property="attempt_count", type="integer", minimum=1, maximum=5, description="Recording attempt count"),
 *             @OA\Property(property="is_final", type="boolean", description="Mark as final answer"),
 *             @OA\Property(
 *                 property="audio_metadata",
 *                 type="object",
 *                 @OA\Property(property="sample_rate", type="integer", minimum=8000, maximum=48000, example=44100),
 *                 @OA\Property(property="bit_rate", type="integer", minimum=32, maximum=320, example=128),
 *                 @OA\Property(property="channels", type="integer", enum={1, 2}, example=1),
 *                 @OA\Property(property="format", type="string", enum={"mp3", "wav", "m4a", "aac", "ogg", "webm"}, example="mp3"),
 *                 @OA\Property(property="file_size_bytes", type="integer", minimum=1, example=1048576)
 *             ),
 *             @OA\Property(
 *                 property="speech_analysis",
 *                 type="object",
 *                 @OA\Property(property="speaking_rate", type="number", minimum=0, example=150.5, description="Words per minute"),
 *                 @OA\Property(property="pause_count", type="integer", minimum=0, example=12, description="Number of pauses"),
 *                 @OA\Property(property="pause_duration", type="number", minimum=0, example=8.5, description="Total pause duration in seconds"),
 *                 @OA\Property(property="volume_level", type="string", enum={"very_low", "low", "normal", "high", "very_high"}, example="normal")
 *             ),
 *             @OA\Property(property="student_notes", type="string", maxLength=1000, description="Optional student notes")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Answer submitted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingAnswer"),
 *             @OA\Property(property="message", type="string", example="Answer submitted successfully")
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
 *     path="/speaking/submissions/{id}/submit",
 *     tags={"Speaking Submissions"},
 *     summary="Submit complete speaking submission",
 *     description="Finalize and submit the complete speaking submission for grading",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="force_submit", type="boolean", description="Force submit even if incomplete"),
 *             @OA\Property(property="time_spent", type="integer", minimum=0, maximum=7200, description="Total time spent in seconds"),
 *             @OA\Property(property="completion_notes", type="string", maxLength=1000, description="Completion notes"),
 *             @OA\Property(
 *                 property="self_assessment",
 *                 type="object",
 *                 @OA\Property(property="difficulty_rating", type="integer", minimum=1, maximum=5, description="Task difficulty rating"),
 *                 @OA\Property(property="confidence_rating", type="integer", minimum=1, maximum=5, description="Confidence level"),
 *                 @OA\Property(property="effort_rating", type="integer", minimum=1, maximum=5, description="Effort level"),
 *                 @OA\Property(property="comments", type="string", maxLength=500, description="Self-assessment comments")
 *             ),
 *             @OA\Property(
 *                 property="technical_issues",
 *                 type="object",
 *                 @OA\Property(property="had_issues", type="boolean", description="Experienced technical issues"),
 *                 @OA\Property(property="issue_description", type="string", maxLength=1000, description="Description of issues"),
 *                 @OA\Property(property="audio_quality", type="string", enum={"excellent", "good", "fair", "poor"}, description="Audio quality rating")
 *             ),
 *             @OA\Property(property="final_review", type="boolean", description="Final review completed")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Submission completed successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingSubmissionResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation or completion error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/speaking/recordings/upload",
 *     tags={"Speaking Recordings"},
 *     summary="Upload audio recording",
 *     description="Upload an audio recording file for a speaking question",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="submission_id", type="string", format="uuid", description="Submission UUID"),
 *                 @OA\Property(property="question_id", type="string", format="uuid", description="Question UUID"),
 *                 @OA\Property(
 *                     property="audio_file",
 *                     type="string",
 *                     format="binary",
 *                     description="Audio file (mp3, wav, m4a, aac, ogg, webm - max 50MB)"
 *                 ),
 *                 @OA\Property(property="duration_seconds", type="number", minimum=1, maximum=1800, description="Recording duration"),
 *                 @OA\Property(property="file_size_bytes", type="integer", minimum=1, description="File size in bytes"),
 *                 @OA\Property(property="recording_quality", type="string", enum={"high", "medium", "low"}, description="Recording quality"),
 *                 @OA\Property(property="sample_rate", type="integer", minimum=8000, maximum=48000, description="Audio sample rate"),
 *                 @OA\Property(property="bit_rate", type="integer", minimum=32, maximum=320, description="Audio bit rate"),
 *                 @OA\Property(property="channels", type="integer", enum={1, 2}, description="Audio channels"),
 *                 @OA\Property(property="recording_device", type="string", maxLength=255, description="Recording device info"),
 *                 @OA\Property(property="is_final", type="boolean", description="Mark as final recording")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Audio uploaded successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="audio_url", type="string", format="uri", description="Uploaded audio URL"),
 *                 @OA\Property(property="transcript", type="string", description="Generated transcript"),
 *                 @OA\Property(property="confidence_score", type="number", minimum=0, maximum=1, description="Recognition confidence"),
 *                 @OA\Property(
 *                     property="speech_analysis",
 *                     type="object",
 *                     @OA\Property(property="speaking_rate", type="number", description="Words per minute"),
 *                     @OA\Property(property="pause_count", type="integer", description="Number of pauses"),
 *                     @OA\Property(property="clarity_score", type="number", minimum=0, maximum=1, description="Speech clarity"),
 *                     @OA\Property(property="fluency_score", type="number", minimum=0, maximum=1, description="Speech fluency")
 *                 )
 *             ),
 *             @OA\Property(property="message", type="string", example="Audio uploaded and processed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     ),
 *     @OA\Response(
 *         response=413,
 *         description="File too large",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/speaking/submissions",
 *     tags={"Speaking Submissions"},
 *     summary="Get student's speaking submissions",
 *     description="Retrieve paginated list of student's speaking submissions",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         @OA\Schema(type="integer", minimum=1, default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Items per page",
 *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by submission status",
 *         @OA\Schema(type="string", enum={"draft", "in_progress", "submitted", "graded"})
 *     ),
 *     @OA\Parameter(
 *         name="assignment_id",
 *         in="query",
 *         description="Filter by assignment",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/SpeakingSubmission")
 *                 ),
 *                 @OA\Property(property="total", type="integer", example=25)
 *             ),
 *             @OA\Property(property="message", type="string", example="Speaking submissions retrieved successfully")
 *         )
 *     )
 * )
 */