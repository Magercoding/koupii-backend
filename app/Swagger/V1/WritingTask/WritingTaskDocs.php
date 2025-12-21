<?php

namespace App\Swagger\V1\WritingTask;

use OpenApi\Annotations as OA;

class WritingTaskDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/writing-tasks",
     *     tags={"Writing Tasks"},
     *     summary="Get list of writing tasks",
     *     description="Retrieve writing tasks based on user role - Admin sees all, Teacher sees own tasks, Student sees assigned published tasks",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing tasks retrieved successfully",
     *             @OA\Property(property="message", type="string", example="Writing tasks retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="is_published", type="boolean"),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="creator",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="assignments",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="classroom_id", type="string", format="uuid"),
     *                             @OA\Property(property="due_date", type="string", format="date-time", nullable=true)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks",
     *     tags={"Writing Tasks"},
     *     summary="Create a new writing task",
     *     description="Create a new writing task with questions and assignments",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Essay Writing Task"),
     *             @OA\Property(property="description", type="string", example="Write a 500-word essay about climate change"),
     *             @OA\Property(property="instructions", type="string", example="Follow the essay structure: introduction, body, conclusion"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="time_limit_seconds", type="integer", example=3600, nullable=true),
     *             @OA\Property(property="allow_file_upload", type="boolean", example=true),
     *             @OA\Property(property="max_file_size_mb", type="integer", example=10),
     *             @OA\Property(property="allowed_file_types", type="array", @OA\Items(type="string"), example={"pdf", "doc", "docx"}),
     *             @OA\Property(
     *                 property="classroom_assignments",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="classroom_id", type="string", format="uuid"),
     *                     @OA\Property(property="due_date", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Writing task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing task created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_published", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-tasks/{id}",
     *     tags={"Writing Tasks"},
     *     summary="Get specific writing task details",
     *     description="Retrieve details of a specific writing task with submissions and assignments",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing task retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="difficulty", type="string"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                 @OA\Property(property="is_published", type="boolean"),
     *                 @OA\Property(
     *                     property="submissions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="student_id", type="string", format="uuid"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="score", type="integer", nullable=true),
     *                         @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found or unauthorized access"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/writing-tasks/{id}",
     *     tags={"Writing Tasks"},
     *     summary="Update writing task",
     *     description="Update an existing writing task details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *             @OA\Property(property="is_published", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing task updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=500, description="Update failed")
     * )
     */
    public function update()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/writing-tasks/{id}",
     *     tags={"Writing Tasks"},
     *     summary="Delete writing task",
     *     description="Delete a writing task and all associated data",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=500, description="Failed to delete task")
     * )
     */
    public function destroy()
    {
    }
}

