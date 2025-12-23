<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingTaskDocs
{
// ===== SPEAKING TASK ENDPOINTS =====

/**
 * @OA\Get(
 *     path="/speaking/tasks",
 *     tags={"Speaking Tasks"},
 *     summary="Get speaking tasks list",
 *     description="Retrieve paginated list of speaking tasks with optional filters",
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
 *         @OA\Schema(type="string", enum={"beginner", "elementary", "intermediate", "upper_intermediate", "advanced", "proficiency"})
 *     ),
 *     @OA\Parameter(
 *         name="is_published",
 *         in="query",
 *         description="Filter by publication status",
 *         @OA\Schema(type="boolean")
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search by title or description",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingTaskCollection")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class SpeakingTaskIndex {}

/**
 * @OA\Post(
 *     path="/speaking/tasks",
 *     tags={"Speaking Tasks"},
 *     summary="Create speaking task",
 *     description="Create a new speaking task with sections and questions (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title", "difficulty", "timer_type", "sections"},
 *             @OA\Property(property="title", type="string", maxLength=255, example="IELTS Speaking Test"),
 *             @OA\Property(property="description", type="string", maxLength=1000, example="IELTS style speaking test with 3 parts"),
 *             @OA\Property(property="instructions", type="string", maxLength=2000, example="Answer all questions clearly and naturally"),
 *             @OA\Property(property="difficulty", type="string", enum={"beginner", "elementary", "intermediate", "upper_intermediate", "advanced", "proficiency"}, example="intermediate"),
 *             @OA\Property(property="timer_type", type="string", enum={"none", "per_question", "total_test"}, example="per_question"),
 *             @OA\Property(property="time_limit_seconds", type="integer", minimum=60, maximum=7200, example=900),
 *             @OA\Property(property="allow_repetition", type="boolean", example=false),
 *             @OA\Property(property="max_repetition_count", type="integer", minimum=1, maximum=5, example=3),
 *             @OA\Property(property="is_published", type="boolean", example=false),
 *             @OA\Property(
 *                 property="sections",
 *                 type="array",
 *                 @OA\Items(
 *                     required={"title", "order_index", "questions"},
 *                     @OA\Property(property="title", type="string", maxLength=255, example="Part 1: Introduction"),
 *                     @OA\Property(property="instructions", type="string", maxLength=1000, example="Answer questions about yourself"),
 *                     @OA\Property(property="order_index", type="integer", minimum=0, example=0),
 *                     @OA\Property(property="time_limit_seconds", type="integer", minimum=30, maximum=1800, example=300),
 *                     @OA\Property(
 *                         property="questions",
 *                         type="array",
 *                         @OA\Items(
 *                             required={"topic", "prompt", "response_time_seconds", "order_index"},
 *                             @OA\Property(property="topic", type="string", maxLength=255, example="Personal Information"),
 *                             @OA\Property(property="prompt", type="string", maxLength=2000, example="Tell me about your hometown."),
 *                             @OA\Property(property="preparation_time_seconds", type="integer", minimum=15, maximum=300, example=30),
 *                             @OA\Property(property="response_time_seconds", type="integer", minimum=30, maximum=300, example=120),
 *                             @OA\Property(property="order_index", type="integer", minimum=0, example=0),
 *                             @OA\Property(property="sample_answer", type="string", maxLength=2000, example="My hometown is..."),
 *                             @OA\Property(
 *                                 property="evaluation_criteria",
 *                                 type="array",
 *                                 @OA\Items(type="string", maxLength=500),
 *                                 example={"fluency", "pronunciation", "vocabulary", "grammar"}
 *                             )
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Speaking task created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 *     )
 * )
 */
public function store() {}

/**
 * @OA\Get(
 *     path="/speaking/tasks/{id}",
 *     tags={"Speaking Tasks"},
 *     summary="Get speaking task details",
 *     description="Retrieve detailed information about a specific speaking task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task details retrieved successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingTaskDetailResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function show() {}

/**
 * @OA\Put(
 *     path="/speaking/tasks/{id}",
 *     tags={"Speaking Tasks"},
 *     summary="Update speaking task",
 *     description="Update an existing speaking task (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", maxLength=255, example="Updated Speaking Test"),
 *             @OA\Property(property="description", type="string", maxLength=1000, example="Updated description"),
 *             @OA\Property(property="instructions", type="string", maxLength=2000, example="Updated instructions"),
 *             @OA\Property(property="difficulty", type="string", enum={"beginner", "elementary", "intermediate", "upper_intermediate", "advanced", "proficiency"}, example="advanced"),
 *             @OA\Property(property="timer_type", type="string", enum={"none", "per_question", "total_test"}, example="total_test"),
 *             @OA\Property(property="time_limit_seconds", type="integer", minimum=60, maximum=7200, example=1200),
 *             @OA\Property(property="allow_repetition", type="boolean", example=true),
 *             @OA\Property(property="max_repetition_count", type="integer", minimum=1, maximum=5, example=2),
 *             @OA\Property(property="is_published", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 *     )
 * )
 */
public function update() {}

/**
 * @OA\Delete(
 *     path="/speaking/tasks/{id}",
 *     tags={"Speaking Tasks"},
 *     summary="Delete speaking task",
 *     description="Delete a speaking task (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Speaking task deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function destroy() {}

/**
 * @OA\Post(
 *     path="/speaking/tasks/{id}/duplicate",
 *     tags={"Speaking Tasks"},
 *     summary="Duplicate speaking task",
 *     description="Create a copy of an existing speaking task (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID to duplicate",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", maxLength=255, example="Copy of Speaking Test"),
 *             @OA\Property(property="description", type="string", maxLength=1000, example="Duplicated speaking test")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Speaking task duplicated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SpeakingTaskResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function duplicate() {}

/**
 * @OA\Patch(
 *     path="/speaking/tasks/{id}/publish",
 *     tags={"Speaking Tasks"},
 *     summary="Publish speaking task",
 *     description="Publish a speaking task to make it available for assignments (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task published successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Speaking task published successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTask")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function publish() {}

/**
 * @OA\Patch(
 *     path="/speaking/tasks/{id}/unpublish",
 *     tags={"Speaking Tasks"},
 *     summary="Unpublish speaking task",
 *     description="Unpublish a speaking task to make it unavailable for new assignments (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task unpublished successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Speaking task unpublished successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTask")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function unpublish() {}

/**
 * @OA\Post(
 *     path="/speaking/tasks/{id}/assign",
 *     tags={"Speaking Tasks"},
 *     summary="Assign speaking task",
 *     description="Assign a speaking task to students or classes (Admin/Teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Speaking task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"assignment_type"},
 *             @OA\Property(property="assignment_type", type="string", enum={"class", "individual"}, example="class"),
 *             @OA\Property(
 *                 property="class_ids",
 *                 type="array",
 *                 @OA\Items(type="string", format="uuid"),
 *                 example={"123e4567-e89b-12d3-a456-426614174000"}
 *             ),
 *             @OA\Property(
 *                 property="student_ids",
 *                 type="array",
 *                 @OA\Items(type="string", format="uuid"),
 *                 example={"123e4567-e89b-12d3-a456-426614174001"}
 *             ),
 *             @OA\Property(property="due_date", type="string", format="date-time", example="2025-12-31T23:59:59Z"),
 *             @OA\Property(property="allow_retake", type="boolean", example=true),
 *             @OA\Property(property="max_attempts", type="integer", minimum=1, maximum=5, example=3)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Speaking task assigned successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Speaking task assigned successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 @OA\Property(property="assignments_created", type="integer", example=25),
 *                 @OA\Property(property="classes_assigned", type="integer", example=1),
 *                 @OA\Property(property="students_assigned", type="integer", example=25)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Admin/Teacher access required",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Speaking task not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/v1/speaking/tasks",
 *     summary="Get all speaking tasks",
 *     tags={"Speaking Tasks"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Speaking tasks retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SpeakingTaskResource"))
 *         )
 *     )
 * )
 */
public function index() {}
     * @OA\Post(
     *     path="/api/v1/speaking/tasks",
     *     summary="Create a new speaking task",
     *     tags={"Speaking Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Introduce Yourself"),
     *             @OA\Property(property="description", type="string", example="Speaking task description"),
     *             @OA\Property(property="instructions", type="string", example="Speak for 2 minutes about yourself"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}),
     *             @OA\Property(property="time_limit_seconds", type="integer", example=120),
     *             @OA\Property(property="sections", type="array", @OA\Items(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="questions", type="array", @OA\Items(
     *                     @OA\Property(property="topic", type="string"),
     *                     @OA\Property(property="prompt", type="string"),
     *                     @OA\Property(property="preparation_time_seconds", type="integer"),
     *                     @OA\Property(property="response_time_seconds", type="integer")
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Speaking task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking task created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTaskResource")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/tasks/{speakingTask}",
     *     summary="Get speaking task details",
     *     tags={"Speaking Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="speakingTask",
     *         in="path",
     *         description="Speaking Task ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTaskResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Speaking task not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/v1/speaking/tasks/{speakingTask}",
     *     summary="Update speaking task",
     *     tags={"Speaking Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="speakingTask",
     *         in="path",
     *         description="Speaking Task ID",
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
     *             @OA\Property(property="time_limit_seconds", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking task updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingTaskResource")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only"),
     *     @OA\Response(response=404, description="Speaking task not found")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/speaking/tasks/{speakingTask}",
     *     summary="Delete speaking task",
     *     tags={"Speaking Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="speakingTask",
     *         in="path",
     *         description="Speaking Task ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only"),
     *     @OA\Response(response=404, description="Speaking task not found")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/tasks/{speakingTask}/assign",
     *     summary="Assign speaking task to students",
     *     tags={"Speaking Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="speakingTask",
     *         in="path",
     *         description="Speaking Task ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="class_id", type="string", format="uuid"),
     *             @OA\Property(property="student_ids", type="array", @OA\Items(type="string", format="uuid")),
     *             @OA\Property(property="due_date", type="string", format="date-time"),
     *             @OA\Property(property="allow_retake", type="boolean"),
     *             @OA\Property(property="max_attempts", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Speaking task assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Speaking task assigned to 5 students"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="assigned_count", type="integer", example=5),
     *                 @OA\Property(property="assignment_ids", type="array", @OA\Items(type="string", format="uuid"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function assign() {}
}