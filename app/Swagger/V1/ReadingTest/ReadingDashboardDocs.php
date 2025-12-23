<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading-dashboard/student",
     *     tags={"Reading Dashboard"},
     *     summary="Get student reading dashboard",
     *     description="Retrieve reading dashboard data for students",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Student reading dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Student reading dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="test_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="status", type="string", enum={"not_started", "in_progress", "completed"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_count", type="integer"),
     *                     @OA\Property(property="last_attempt", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="time_spent", type="integer", nullable=true),
     *                     @OA\Property(property="passages_count", type="integer"),
     *                     @OA\Property(property="questions_count", type="integer")
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
     *     path="/api/v1/reading-dashboard/teacher",
     *     tags={"Reading Dashboard"},
     *     summary="Get teacher reading dashboard",
     *     description="Retrieve reading dashboard data for teachers",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Teacher reading dashboard retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Teacher reading dashboard retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_tests", type="integer"),
     *                 @OA\Property(property="published_tests", type="integer"),
     *                 @OA\Property(property="draft_tests", type="integer"),
     *                 @OA\Property(property="total_submissions", type="integer"),
     *                 @OA\Property(property="pending_reviews", type="integer"),
     *                 @OA\Property(property="average_score", type="number", nullable=true),
     *                 @OA\Property(
     *                     property="recent_tests",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="submissions_count", type="integer"),
     *                         @OA\Property(property="average_score", type="number", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
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
     *     path="/api/v1/reading-dashboard/analytics",
     *     tags={"Reading Dashboard"},
     *     summary="Get reading analytics",
     *     description="Retrieve detailed analytics for reading tests",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Reading analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading analytics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="completion_rate", type="number"),
     *                 @OA\Property(property="average_time", type="integer"),
     *                 @OA\Property(property="difficulty_breakdown", type="object"),
     *                 @OA\Property(property="performance_trends", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function analytics()
    {
    }
}