<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening-dashboard/student",
     *     tags={"Listening Dashboard"},
     *     summary="Get student listening dashboard",
     *     description="Get listening task assignments for student with status and progress",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Student dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Student dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="IELTS Listening Practice"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "submitted", "reviewed", "done"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="can_retake", type="boolean"),
     *                     @OA\Property(property="is_overdue", type="boolean"),
     *                     @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="total_questions", type="integer"),
     *                     @OA\Property(property="has_audio", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Student only")
     * )
     */
    public function student() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening-dashboard/teacher",
     *     tags={"Listening Dashboard"},
     *     summary="Get teacher listening dashboard",
     *     description="Get listening task assignments and submissions for teacher overview",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Teacher dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Teacher dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="class_name", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="total_submissions", type="integer"),
     *                     @OA\Property(property="pending_reviews", type="integer"),
     *                     @OA\Property(property="completed_reviews", type="integer"),
     *                     @OA\Property(property="average_score", type="number", format="float", nullable=true),
     *                     @OA\Property(property="audio_duration_seconds", type="integer", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Teacher only")
     * )
     */
    public function teacher() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening-tasks/{taskId}/assignment/{assignmentId}",
     *     tags={"Listening Dashboard"},
     *     summary="Get listening task detail for student",
     *     description="Get detailed listening task with audio and questions for student attempt",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening task detail retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening task detail retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="task_id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="audio_file_url", type="string"),
     *                 @OA\Property(property="timer_type", type="string"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                 @OA\Property(property="can_retake", type="boolean"),
     *                 @OA\Property(property="max_attempts", type="integer"),
     *                 @OA\Property(property="current_attempt", type="integer"),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="question_type", type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10"}),
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="options", type="array", @OA\Items(type="string"), nullable=true),
     *                         @OA\Property(property="order", type="integer"),
     *                         @OA\Property(property="points", type="number")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="audio_segments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="start_time", type="number", format="float"),
     *                         @OA\Property(property="end_time", type="number", format="float"),
     *                         @OA\Property(property="text", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task or assignment not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function getTaskDetail() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening-submissions/{submissionId}/results",
     *     tags={"Listening Dashboard"},
     *     summary="Get listening test results with explanations",
     *     description="Get listening test results with audio segments and vocabulary discovery",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening test results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening test results retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="score", type="integer"),
     *                 @OA\Property(property="total_score", type="integer"),
     *                 @OA\Property(property="percentage", type="number", format="float"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="question_results",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="question_id", type="string", format="uuid"),
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="student_answer", type="string"),
     *                         @OA\Property(property="correct_answer", type="string"),
     *                         @OA\Property(property="is_correct", type="boolean"),
     *                         @OA\Property(property="points_earned", type="number"),
     *                         @OA\Property(property="audio_segment", type="object", nullable=true,
     *                             @OA\Property(property="start_time", type="number"),
     *                             @OA\Property(property="end_time", type="number"),
     *                             @OA\Property(property="transcript", type="string")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="vocabulary_discovered",
     *                     type="array",
     *                     description="New vocabulary words discovered from this listening",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="word", type="string"),
     *                         @OA\Property(property="definition", type="string"),
     *                         @OA\Property(property="context", type="string"),
     *                         @OA\Property(property="audio_timestamp", type="number", format="float")
     *                     )
     *                 ),
     *                 @OA\Property(property="audio_file_url", type="string"),
     *                 @OA\Property(property="transcript_available", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function getResults() {}
}