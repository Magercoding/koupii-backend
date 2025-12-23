<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingTaskAssignmentDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading-assignments",
     *     tags={"Reading Assignments"},
     *     summary="Get reading task assignments",
     *     description="Get reading task assignments for teachers and students",
     *     security={{"bearerAuth":{}}},
     *   
     *     @OA\Response(
     *         response=200,
     *         description="Reading assignments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading assignments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="class_id", type="string", format="uuid"),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="allow_retake", type="boolean"),
     *                     @OA\Property(property="max_attempts", type="integer"),
     *                     @OA\Property(property="assigned_at", type="string", format="date-time"),
     *                     @OA\Property(property="task_title", type="string"),
     *                     @OA\Property(property="class_name", type="string")
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
     *     path="/api/v1/reading-assignments",
     *     tags={"Reading Assignments"},
     *     summary="Assign reading task to class",
     *     description="Assign a reading task to a class (Teacher only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="task_id", type="string", format="uuid", description="Reading task to assign"),
     *             @OA\Property(property="class_id", type="string", format="uuid", description="Class to assign to"),
     *             @OA\Property(property="due_date", type="string", format="date-time", description="Assignment due date"),
     *             @OA\Property(property="allow_retake", type="boolean", description="Allow students to retake"),
     *             @OA\Property(property="max_attempts", type="integer", description="Maximum attempts allowed", example=3),
     *             @OA\Property(property="instructions", type="string", nullable=true, description="Additional instructions for this assignment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading task assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task assigned to class successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="students_assigned", type="integer"),
     *                 @OA\Property(property="due_date", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - Teacher only")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/reading-assignments/{id}",
     *     tags={"Reading Assignments"},
     *     summary="Get assignment details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assignment details retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="task_id", type="string", format="uuid"),
     *                 @OA\Property(property="class_id", type="string", format="uuid"),
     *                 @OA\Property(property="due_date", type="string", format="date-time"),
     *                 @OA\Property(property="allow_retake", type="boolean"),
     *                 @OA\Property(property="max_attempts", type="integer"),
     *                 @OA\Property(
     *                     property="task",
     *                     type="object",
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="difficulty_level", type="string"),
     *                     @OA\Property(property="total_questions", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="progress",
     *                     type="object",
     *                     @OA\Property(property="total_students", type="integer"),
     *                     @OA\Property(property="submitted", type="integer"),
     *                     @OA\Property(property="pending", type="integer"),
     *                     @OA\Property(property="reviewed", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/v1/reading-assignments/{id}",
     *     tags={"Reading Assignments"},
     *     summary="Update assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="allow_retake", type="boolean"),
     *             @OA\Property(property="max_attempts", type="integer"),
     *             @OA\Property(property="instructions", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading assignment updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/reading-assignments/{id}",
     *     tags={"Reading Assignments"},
     *     summary="Delete assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading assignment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found")
     * )
     */
    public function destroy() {}
}