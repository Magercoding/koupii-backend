<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingTaskDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/speaking-tasks",
     *     tags={"Speaking Tasks"},
     *     summary="Get list of speaking tasks",
     *     description="Retrieve speaking tasks based on user role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Speaking tasks retrieved successfully"),
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
     *                     @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}),
     *                     @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
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
     *     path="/api/v1/speaking-tasks",
     *     tags={"Speaking Tasks"},
     *     summary="Create a new speaking task",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description"},
     *                 @OA\Property(property="title", type="string", example="Speaking Test"),
     *                 @OA\Property(property="description", type="string", example="Description of speaking test"),
     *                 @OA\Property(property="instructions", type="string", example="Instructions for speaking test"),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *                 @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}, example="countdown"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", nullable=true, example=300),
     *                 @OA\Property(property="task_type", type="string", example="presentation"),
     *                 @OA\Property(property="points", type="integer", example=100),
     *                 @OA\Property(property="is_published", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="prompt_audio",
     *                     type="string",
     *                     format="binary",
     *                     description="Upload audio prompt file (optional)"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_materials",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload reference materials (images, documents, etc.)"
     *                 ),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="string",
     *                     description="JSON string of questions array",
     *                     example="[{""question_text"":""Describe your hometown"",""time_limit"":60}]"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Speaking task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Speaking task created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking-tasks/{id}",
     *     tags={"Speaking Tasks"},
     *     summary="Get speaking task details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Speaking task retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking-tasks/{id}/update",
     *     tags={"Speaking Tasks"},
     *     summary="Update speaking task",
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
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}),
     *             @OA\Property(property="time_limit_seconds", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Speaking task updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/speaking-tasks/{id}",
     *     tags={"Speaking Tasks"},
     *     summary="Delete speaking task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Speaking task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy() {}
}
