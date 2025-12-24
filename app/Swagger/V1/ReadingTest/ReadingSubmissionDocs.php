<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading-tasks/{taskId}/submissions",
     *     tags={"Reading Submissions"},
     *     summary="Get submissions for a reading task",
     *     description="Retrieve all submissions for a specific reading task (Teacher view)",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading submissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_name", type="string"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="total_score", type="integer"),
     *                     @OA\Property(property="percentage", type="number", format="float", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="time_taken_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-tasks/{taskId}/submissions",
     *     tags={"Reading Submissions"},
     *     summary="Submit reading test answers",
     *     description="Submit answers for a reading test (Student only) - Supports all 15 Question Types",
     *     security={{"bearerAuth":{}}},
     *    
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Reading Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Reading test answers for 15 Question Types: QT1-Multiple Choice, QT2-Multiple Answer, QT3-True/False/Not Given, QT4-Matching Heading, QT5-Sentence Completion, QT6-Paragraph/Summary Completion, QT7-Yes/No/Not Given, QT8-Matching Information, QT9-Matching Features, QT10-Matching Sentence Ending, QT11-Note Completion, QT12-Table Completion, QT13-Flowchart Completion, QT14-Diagram Label Completion, QT15-Short Answer Question",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"answers"},
     *                 @OA\Property(
     *                     property="answers",
     *                     type="string",
     *                     description="JSON string of answers array for each question",
     *                     example="[{""question_id"":""uuid"",""answer"":""A"",""question_type"":""QT1""},{""question_id"":""uuid2"",""answer"":""True"",""question_type"":""QT3""}]"
     *                 ),
     *                 @OA\Property(property="time_taken_seconds", type="integer", description="Total time taken to complete the test", example=3600),
     *                 @OA\Property(property="assignment_id", type="string", format="uuid", description="Assignment ID"),
     *                 @OA\Property(
     *                     property="supporting_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload supporting files, notes, or work sheets (optional)"
     *                 ),
     *                 @OA\Property(
     *                     property="note_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload handwritten notes or additional materials (optional)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading submission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="score", type="integer"),
     *                 @OA\Property(property="total_score", type="integer"),
     *                 @OA\Property(property="percentage", type="number", format="float"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="auto_graded", type="boolean", example=true),
     *                 @OA\Property(property="can_retake", type="boolean"),
     *                 @OA\Property(property="next_attempt_available_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - Student only or assignment not available")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/reading-submissions/{id}",
     *     tags={"Reading Submissions"},
     *     summary="Get submission details",
     *     description="Get detailed submission with answers and grading information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading submission details retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="task_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="score", type="integer"),
     *                 @OA\Property(property="total_score", type="integer"),
     *                 @OA\Property(property="percentage", type="number", format="float"),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="time_taken_seconds", type="integer"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="answers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="question_id", type="string", format="uuid"),
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="student_answer", type="string"),
     *                         @OA\Property(property="correct_answer", type="string"),
     *                         @OA\Property(property="is_correct", type="boolean"),
     *                         @OA\Property(property="points_earned", type="number"),
     *                         @OA\Property(property="feedback", type="string", nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="task_info",
     *                     type="object",
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="passage_text", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-submissions/{id}/retake",
     *     tags={"Reading Submissions"},
     *     summary="Start retake attempt",
     *     description="Start a new attempt for reading test retake",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Original Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retake started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test retake started"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="new_submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="remaining_attempts", type="integer"),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Retake not allowed"),
     *     @OA\Response(response=404, description="Submission not found")
     * )
     */
    public function retake() {}
}

