<?php

namespace App\Swagger\V1\WritingTask;

use OpenApi\Annotations as OA;

class WritingDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/writing-dashboard/student",
     *     tags={"Writing Dashboard"},
     *     summary="Get student dashboard",
     *     description="Retrieve dashboard data for students",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
         response=200,
         description="Student dashboard retrieved successfully",
         @OA\JsonContent(
             @OA\Property(property="message", type="string", example="Student dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "submitted", "reviewed", "done"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="can_retake", type="boolean"),
     *                     @OA\Property(property="is_overdue", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Student only")
     * )
     */
    public function student()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-dashboard/teacher",
     *     tags={"Writing Dashboard"},
     *     summary="Get teacher dashboard",
     *     description="Retrieve dashboard data for teachers",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Teacher dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="total_submissions", type="integer"),
     *                     @OA\Property(property="pending_reviews", type="integer"),
     *                     @OA\Property(property="reviewed_submissions", type="integer"),
     *                     @OA\Property(property="average_score", type="number", format="float"),
     *                     @OA\Property(property="assigned_classes", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Teacher/Admin only")
     * )
     */
    public function teacher()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-dashboard/admin",
     *     tags={"Writing Dashboard"},
     *     summary="Get admin dashboard",
     *     description="Retrieve dashboard data for admins",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Admin dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Admin dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_tasks", type="integer"),
     *                     @OA\Property(property="published_tasks", type="integer"),
     *                     @OA\Property(property="total_submissions", type="integer"),
     *                     @OA\Property(property="pending_reviews", type="integer"),
     *                     @OA\Property(property="average_score", type="number", format="float")
     *                 ),
     *                 @OA\Property(property="recent_tasks", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="top_teachers", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function admin()
    {
    }
}