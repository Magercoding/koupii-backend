<?php

namespace App\Swagger\V1\Listening;

// ===== LISTENING TASK ENDPOINTS =====

/**
 * @OA\Get(
 *     path="/listening/tasks",
 *     tags={"Listening Tasks"},
 *     summary="Get listening tasks list",
 *     description="Retrieve paginated list of listening tasks with optional filters",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         @OA\Schema(type="integer", minimum=1, default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Items per page",
 *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
 *     ),
 *     @OA\Parameter(
 *         name="difficulty",
 *         in="query",
 *         description="Filter by difficulty level",
 *         @OA\Schema(type="string", enum={"beginner", "intermediate", "advanced"})
 *     ),
 *     @OA\Parameter(
 *         name="question_type",
 *         in="query",
 *         description="Filter by question type",
 *         @OA\Schema(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"})
 *     ),
 *     @OA\Parameter(
 *         name="is_published",
 *         in="query",
 *         description="Filter by publication status",
 *         @OA\Schema(type="boolean")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskCollection")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/tasks",
 *     tags={"Listening Tasks"},
 *     summary="Create new listening task",
 *     description="Create a new listening task with audio and questions",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "audio_url", "question_types", "difficulty_level"},
 *             @OA\Property(property="title", type="string", maxLength=255, example="IELTS Listening Practice"),
 *             @OA\Property(property="description", type="string", example="Practice listening task for IELTS preparation"),
 *             @OA\Property(property="audio_url", type="string", format="uri", example="https://example.com/audio/task1.mp3"),
 *             @OA\Property(property="transcript", type="string", example="Full audio transcript..."),
 *             @OA\Property(
 *                 property="question_types", 
 *                 type="array", 
 *                 @OA\Items(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"}),
 *                 example={"QT1", "QT2", "QT3"}
 *             ),
 *             @OA\Property(property="difficulty_level", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
 *             @OA\Property(property="duration", type="integer", example=300, description="Audio duration in seconds"),
 *             @OA\Property(property="max_replays", type="integer", example=3, description="Maximum replays allowed"),
 *             @OA\Property(
 *                 property="replay_controls",
 *                 type="object",
 *                 @OA\Property(property="allow_pause", type="boolean", example=true),
 *                 @OA\Property(property="allow_rewind", type="boolean", example=false),
 *                 @OA\Property(property="show_progress", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Task created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/tasks/{id}",
 *     tags={"Listening Tasks"},
 *     summary="Get listening task details",
 *     description="Retrieve detailed information about a specific listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/listening/tasks/{id}",
 *     tags={"Listening Tasks"},
 *     summary="Update listening task",
 *     description="Update an existing listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="audio_url", type="string", format="uri"),
 *             @OA\Property(property="transcript", type="string"),
 *             @OA\Property(
 *                 property="question_types", 
 *                 type="array", 
 *                 @OA\Items(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"})
 *             ),
 *             @OA\Property(property="difficulty_level", type="string", enum={"beginner", "intermediate", "advanced"}),
 *             @OA\Property(property="duration", type="integer"),
 *             @OA\Property(property="max_replays", type="integer"),
 *             @OA\Property(property="replay_controls", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/listening/tasks/{id}",
 *     tags={"Listening Tasks"},
 *     summary="Delete listening task",
 *     description="Delete a listening task and all associated data",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task deleted successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/tasks/{id}/duplicate",
 *     tags={"Listening Tasks"},
 *     summary="Duplicate listening task",
 *     description="Create a copy of an existing listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID to duplicate",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", description="New task title"),
 *             @OA\Property(property="include_questions", type="boolean", default=true),
 *             @OA\Property(property="include_audio", type="boolean", default=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Task duplicated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskResponse")
 *     )
 * )
 */

/**
 * @OA\Patch(
 *     path="/listening/tasks/{id}/publish",
 *     tags={"Listening Tasks"},
 *     summary="Publish listening task",
 *     description="Make task available to students",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task published successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ListeningTaskResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/tasks/{id}/preview",
 *     tags={"Listening Tasks"},
 *     summary="Preview listening task",
 *     description="Get task preview for testing purposes",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task preview retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="task", ref="#/components/schemas/ListeningTask"),
 *                         @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/ListeningQuestion")),
 *                         @OA\Property(property="audio_segments", type="array", @OA\Items(ref="#/components/schemas/AudioSegment"))
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

