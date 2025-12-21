<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/speaking-dashboard/student",
     *     tags={"Speaking Dashboard"},
     *     summary="Get student speaking dashboard",
     *     description="Retrieve speaking dashboard data for students including assigned speaking tasks and submission status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Student speaking dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Student speaking dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed", "done"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="can_retake", type="boolean"),
     *                     @OA\Property(property="max_attempts", type="integer"),
     *                     @OA\Property(property="is_overdue", type="boolean"),
     *                     @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}),
     *                     @OA\Property(property="total_questions", type="integer"),
     *                     @OA\Property(property="completed_recordings", type="integer")
     *                 )
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
     *     path="/api/v1/speaking-dashboard/teacher",
     *     tags={"Speaking Dashboard"},
     *     summary="Get teacher speaking dashboard",
     *     description="Retrieve speaking dashboard data for teachers including created tasks and student submissions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Teacher speaking dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Teacher speaking dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="is_published", type="boolean"),
     *                     @OA\Property(property="total_submissions", type="integer"),
     *                     @OA\Property(property="pending_reviews", type="integer"),
     *                     @OA\Property(property="reviewed_submissions", type="integer"),
     *                     @OA\Property(property="in_progress_submissions", type="integer"),
     *                     @OA\Property(property="average_score", type="number", format="float", nullable=true),
     *                     @OA\Property(property="assigned_classes", type="integer"),
     *                     @OA\Property(property="total_questions", type="integer"),
     *                     @OA\Property(property="total_recordings", type="integer"),
     *                     @OA\Property(property="completion_rate", type="number", format="float")
     *                 )
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
     *     path="/api/v1/speaking-dashboard/admin",
     *     tags={"Speaking Dashboard"},
     *     summary="Get admin speaking dashboard",
     *     description="Retrieve comprehensive speaking dashboard data for admins with system-wide statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Admin speaking dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Admin speaking dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_tasks", type="integer"),
     *                     @OA\Property(property="published_tasks", type="integer"),
     *                     @OA\Property(property="draft_tasks", type="integer"),
     *                     @OA\Property(property="total_submissions", type="integer"),
     *                     @OA\Property(property="pending_reviews", type="integer"),
     *                     @OA\Property(property="completed_reviews", type="integer"),
     *                     @OA\Property(property="total_recordings", type="integer"),
     *                     @OA\Property(property="average_score", type="number", format="float", nullable=true),
     *                     @OA\Property(property="total_teachers", type="integer"),
     *                     @OA\Property(property="total_students", type="integer"),
     *                     @OA\Property(property="system_completion_rate", type="number", format="float")
     *                 ),
     *                 @OA\Property(
     *                     property="recent_tasks",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="creator_name", type="string"),
     *                         @OA\Property(property="difficulty", type="string"),
     *                         @OA\Property(property="is_published", type="boolean"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="top_teachers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="teacher_id", type="string", format="uuid"),
     *                         @OA\Property(property="teacher_name", type="string"),
     *                         @OA\Property(property="tasks_created", type="integer"),
     *                         @OA\Property(property="reviews_completed", type="integer"),
     *                         @OA\Property(property="average_student_score", type="number", format="float")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="difficulty_distribution",
     *                     type="object",
     *                     @OA\Property(property="beginner", type="integer"),
     *                     @OA\Property(property="intermediate", type="integer"),
     *                     @OA\Property(property="advanced", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="submission_trends",
     *                     type="object",
     *                     @OA\Property(property="this_week", type="integer"),
     *                     @OA\Property(property="last_week", type="integer"),
     *                     @OA\Property(property="this_month", type="integer"),
     *                     @OA\Property(property="growth_rate", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin only"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function admin()
    {
    }
}

