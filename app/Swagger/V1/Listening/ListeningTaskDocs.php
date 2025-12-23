<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningTaskDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/tasks",
     *     tags={"Listening Tasks"},
     *     summary="Get list of listening tasks",
     *     description="Retrieve listening tasks based on user role - Admin sees all, Teacher sees own tasks, Student sees assigned published tasks",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listening tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening tasks retrieved successfully"),
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
     *                     @OA\Property(property="audio_url", type="string"),
     *                     @OA\Property(property="duration", type="integer"),
     *                     @OA\Property(property="transcript", type="string"),
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
     *                         property="questions",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="question_text", type="string"),
     *                             @OA\Property(property="question_type", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/listening/tasks",
     *     tags={"Listening Tasks"},
     *     summary="Create a new listening task",
     *     description="Create a new listening task with audio and questions",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "audio_url"},
     *             @OA\Property(property="title", type="string", example="IELTS Listening Practice"),
     *             @OA\Property(property="description", type="string", example="Practice listening comprehension"),
     *             @OA\Property(property="audio_url", type="string", example="audio/listening_task.mp3"),
     *             @OA\Property(property="transcript", type="string", example="Audio transcript text"),
     *             @OA\Property(property="duration", type="integer", example=300),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *             @OA\Property(property="is_published", type="boolean", example=false),
     *             @OA\Property(
     *                 property="questions",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_text", type="string"),
     *                     @OA\Property(property="question_type", type="string", enum={"multiple_choice", "fill_blank", "true_false"}),
     *                     @OA\Property(property="time_range", type="object"),
     *                     @OA\Property(property="options", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="correct_answer", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Listening task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening task created successfully"),
     *             @OA\Property(property="task_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/listening/tasks/{id}",
     *     tags={"Listening Tasks"},
     *     summary="Get a specific listening task",
     *     description="Retrieve detailed information about a specific listening task",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="audio_url", type="string"),
     *             @OA\Property(property="transcript", type="string"),
     *             @OA\Property(property="duration", type="integer"),
     *             @OA\Property(property="difficulty", type="string"),
     *             @OA\Property(property="time_limit_seconds", type="integer"),
     *             @OA\Property(property="is_published", type="boolean"),
     *             @OA\Property(
     *                 property="questions",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Listening task not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/listening/tasks/{id}",
     *     tags={"Listening Tasks"},
     *     summary="Update a listening task",
     *     description="Update an existing listening task",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="audio_url", type="string"),
     *             @OA\Property(property="transcript", type="string"),
     *             @OA\Property(property="duration", type="integer"),
     *             @OA\Property(property="difficulty", type="string"),
     *             @OA\Property(property="time_limit_seconds", type="integer"),
     *             @OA\Property(property="is_published", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening task updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Listening task not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/listening/tasks/{id}",
     *     tags={"Listening Tasks"},
     *     summary="Delete a listening task",
     *     description="Delete a listening task and all related data",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Listening task not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy()
    {
    }
}