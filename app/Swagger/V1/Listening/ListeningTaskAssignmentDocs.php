<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningTaskAssignmentDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/assignments",
     *     tags={"Listening Task Assignments"},
     *     summary="Get listening task assignments",
     *     description="Retrieve listening task assignments based on user role",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         required=false,
     *         description="Filter by class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="task_id",
     *         in="query",
     *         required=false,
     *         description="Filter by task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening assignments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening assignments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="task_id", type="string", format="uuid"),
     *                     @OA\Property(property="class_id", type="string", format="uuid"),
     *                     @OA\Property(property="due_date", type="string", format="date-time"),
     *                     @OA\Property(property="assigned_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="task",
     *                         type="object",
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="description", type="string"),
     *                         @OA\Property(property="difficulty", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="class",
     *                         type="object",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
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
     *     path="/api/v1/listening/assignments",
     *     tags={"Listening Task Assignments"},
     *     summary="Create a listening task assignment",
     *     description="Assign a listening task to a class",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"task_id", "class_id"},
     *             @OA\Property(property="task_id", type="string", format="uuid"),
     *             @OA\Property(property="class_id", type="string", format="uuid"),
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="instructions", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Listening assignment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening assignment created successfully"),
     *             @OA\Property(property="assignment_id", type="string", format="uuid")
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
     *     path="/api/v1/listening/assignments/{id}",
     *     tags={"Listening Task Assignments"},
     *     summary="Get a specific listening assignment",
     *     description="Retrieve detailed information about a specific listening assignment",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening assignment retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="task_id", type="string", format="uuid"),
     *             @OA\Property(property="class_id", type="string", format="uuid"),
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="assigned_at", type="string", format="date-time"),
     *             @OA\Property(property="submissions_count", type="integer"),
     *             @OA\Property(property="completed_count", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/listening/assignments/{id}",
     *     tags={"Listening Task Assignments"},
     *     summary="Delete a listening assignment",
     *     description="Delete a listening task assignment",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening assignment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening assignment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy()
    {
    }
}