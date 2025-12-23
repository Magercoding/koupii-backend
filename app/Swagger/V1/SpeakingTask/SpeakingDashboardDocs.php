<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/speaking/dashboard/student",
     *     tags={"Speaking Dashboard"},
     *     summary="Get student speaking dashboard",
     *     description="Retrieve speaking dashboard data for students including assigned speaking tasks and submission status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by task status",
     *         @OA\Schema(type="string", enum={"to_do", "in_progress", "submitted", "reviewed"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student speaking dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SpeakingDashboardResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Student only"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function student()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/dashboard/teacher",
     *     tags={"Speaking Dashboard"},
     *     summary="Get teacher speaking dashboard",
     *     description="Retrieve speaking dashboard data for teachers including submission review queue",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         description="Filter by class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher speaking dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teacher dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SpeakingTeacherDashboardResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Teacher/Admin only"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function teacher()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/dashboard/tasks/{assignment}",
     *     tags={"Speaking Dashboard"},
     *     summary="Get speaking task detail for student",
     *     description="Retrieve detailed information about a specific speaking assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assignment",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task detail retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTaskDetailResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function getTaskDetail() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/analytics/class/{classId}",
     *     tags={"Speaking Analytics"},
     *     summary="Get class speaking analytics",
     *     description="Retrieve speaking analytics for a specific class",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         description="Class ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="class_id", type="string", format="uuid"),
     *                 @OA\Property(property="total_assignments", type="integer"),
     *                 @OA\Property(property="completed_submissions", type="integer"),
     *                 @OA\Property(property="average_score", type="number", format="float"),
     *                 @OA\Property(property="completion_rate", type="number", format="float"),
     *                 @OA\Property(property="speech_quality_metrics", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function getClassAnalytics() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/analytics/student/{studentId}",
     *     tags={"Speaking Analytics"},
     *     summary="Get student speaking analytics",
     *     description="Retrieve speaking analytics for a specific student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         description="Student ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="total_submissions", type="integer"),
     *                 @OA\Property(property="average_score", type="number", format="float"),
     *                 @OA\Property(property="improvement_trend", type="object"),
     *                 @OA\Property(property="speech_quality_progress", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function getStudentAnalytics() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/analytics/speech-quality",
     *     tags={"Speaking Analytics"},
     *     summary="Get speech quality report",
     *     description="Retrieve comprehensive speech quality analytics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Speech quality report retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="average_confidence", type="number", format="float"),
     *                 @OA\Property(property="average_fluency", type="number", format="float"),
     *                 @OA\Property(property="speaking_rate_distribution", type="object"),
     *                 @OA\Property(property="transcription_accuracy", type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function getSpeechQualityReport() {}
}

