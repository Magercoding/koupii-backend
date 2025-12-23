<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/speaking/student/dashboard",
     *     tags={"Speaking Dashboard"},
     *     summary="Get student speaking dashboard",
     *     description="Get speaking assignments for student dashboard",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
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
    public function student() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/teacher/dashboard",
     *     tags={"Speaking Dashboard"},
     *     summary="Get teacher speaking dashboard",
     *     description="Get speaking assignments for teacher dashboard",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
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
    public function teacher() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/assignments/{assignmentId}/detail",
     *     tags={"Speaking Dashboard"},
     *     summary="Get speaking task detail",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task detail retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function getTaskDetail() {}
}

