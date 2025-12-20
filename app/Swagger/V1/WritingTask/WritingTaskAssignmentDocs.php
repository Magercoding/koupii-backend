<?php

namespace App\Swagger\V1\WritingTask;

use OpenApi\Annotations as OA;

class WritingTaskAssignmentDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{id}/assignments",
     *     tags={"Writing Task Assignments"},
     *     summary="Assign task to classrooms",
     *     description="Assign a writing task to multiple classrooms (Teacher/Admin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"classroom_ids"},
     *             @OA\Property(
     *                 property="classroom_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid"),
     *                 example={"uuid1", "uuid2", "uuid3"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task sent to classrooms successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task sent to classrooms successfully"),
     *             @OA\Property(property="assignments_count", type="integer", example=3),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function assignToClassrooms()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-tasks/{id}/assignments",
     *     tags={"Writing Task Assignments"},
     *     summary="Get task assignments",
     *     description="Retrieve all classroom assignments for a specific task",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task assignments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task assignments retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function getAssignments()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/writing-tasks/{id}/assignments/{classroomId}",
     *     tags={"Writing Task Assignments"},
     *     summary="Remove task from classroom",
     *     description="Remove a writing task assignment from a specific classroom",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="classroomId",
     *         in="path",
     *         required=true,
     *         description="Classroom ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task removed from classroom successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task removed from classroom successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task or classroom not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function removeFromClassroom()
    {
    }
}