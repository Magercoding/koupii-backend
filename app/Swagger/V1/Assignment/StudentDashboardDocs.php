<?php

namespace App\Swagger\V1\Assignment;

use OpenApi\Annotations as OA;

class StudentDashboardDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/student/dashboard",
     *     tags={"Student Dashboard"},
     *     summary="Get student dashboard with assignments",
     *     description="Retrieve student dashboard showing all assignments from enrolled classes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Dashboard data retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="student_name", type="string", example="John Doe"),
     *                 @OA\Property(property="enrolled_classes", type="integer", example=3),
     *                 @OA\Property(property="total_assignments", type="integer", example=12),
     *                 @OA\Property(property="pending_assignments", type="integer", example=5),
     *                 @OA\Property(property="completed_assignments", type="integer", example=7),
     *                 @OA\Property(property="overdue_assignments", type="integer", example=1),
     *                 @OA\Property(
     *                     property="assignments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="type", type="string", enum={"writing_task", "reading_task", "listening_task", "speaking_task"}),
     *                         @OA\Property(property="title", type="string", example="Essay Writing Practice"),
     *                         @OA\Property(property="description", type="string", example="Academic writing task"),
     *                         @OA\Property(property="class_name", type="string", example="IELTS Preparation A"),
     *                         @OA\Property(property="due_date", type="string", format="date-time"),
     *                         @OA\Property(property="assigned_date", type="string", format="date-time"),
     *                         @OA\Property(property="status", type="string", enum={"pending", "in_progress", "submitted", "completed"}),
     *                         @OA\Property(property="score", type="number", nullable=true),
     *                         @OA\Property(property="attempt_count", type="integer", example=1),
     *                         @OA\Property(property="max_attempts", type="integer", example=3),
     *                         @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"})
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function dashboard() {}

    /**
     * @OA\Get(
     *     path="/api/v1/student/assignments/{assignmentId}/{type}/details",
     *     tags={"Student Dashboard"},
     *     summary="Get assignment details for starting",
     *     description="Retrieve detailed assignment information before starting",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Assignment type",
     *         @OA\Schema(type="string", enum={"writing_task", "reading_task", "listening_task", "speaking_task"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="assignment",
     *                     type="object",
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="instructions", type="string"),
     *                     @OA\Property(property="time_limit", type="integer"),
     *                     @OA\Property(property="max_attempts", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="student_progress",
     *                     type="object",
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="attempt_count", type="integer"),
     *                     @OA\Property(property="score", type="number", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Assignment not found")
     * )
     */
    public function getAssignmentDetails() {}

    /**
     * @OA\Post(
     *     path="/api/v1/student/assignments/{assignmentId}/{type}/start",
     *     tags={"Student Dashboard"},
     *     summary="Start an assignment",
     *     description="Start working on an assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Assignment type",
     *         @OA\Schema(type="string", enum={"writing_task", "reading_task", "listening_task", "speaking_task"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assignment started successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="student_assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="attempt_number", type="integer", example=1),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Assignment not found")
     * )
     */
    public function startAssignment() {}

    /**
     * @OA\Post(
     *     path="/api/v1/student/assignments/{assignmentId}/{type}/submit",
     *     tags={"Student Dashboard"},
     *     summary="Submit an assignment",
     *     description="Submit completed assignment work",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assignmentId",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Assignment type",
     *         @OA\Schema(type="string", enum={"writing_task", "reading_task", "listening_task", "speaking_task"})
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="submission_data",
     *                     type="object",
     *                     description="Assignment submission data (varies by type)",
     *                     @OA\Property(property="answers", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="essay_text", type="string"),
     *                     @OA\Property(property="time_spent", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assignment submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="student_assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time"),
     *                 @OA\Property(property="submission_status", type="string", example="submitted")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No active assignment found")
     * )
     */
    public function submitAssignment() {}
}