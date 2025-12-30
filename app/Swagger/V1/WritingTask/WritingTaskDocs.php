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
     *     @OA\Response(
     *         response=200,
     *         description="Writing tasks retrieved successfully",
     *         @OA\JsonContent(
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "timer_type"},
     *             @OA\Property(property="title", type="string", example="Essay Writing Task"),
     *             @OA\Property(property="description", type="string", example="Write a 500-word essay about climate change"),
     *             @OA\Property(property="instructions", type="string", example="Follow the essay structure: introduction, body, conclusion"),
     *             @OA\Property(property="sample_answer", type="string", example="Sample answer for reference"),
     *             @OA\Property(property="word_limit", type="integer", example=500, nullable=true),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="timer_type", type="string", enum={"none", "countdown", "countup"}, example="countdown"),
     *             @OA\Property(property="time_limit_seconds", type="integer", example=3600, nullable=true),
     *             @OA\Property(property="allow_retake", type="boolean", example=true),
     *             @OA\Property(property="max_retake_attempts", type="integer", example=3, nullable=true),
     *             @OA\Property(
     *                 property="retake_options",
     *                 type="array",
     *                 @OA\Items(type="string", enum={"rewrite_all", "group_similar", "choose_any"}),
     *                 example={"rewrite_all", "group_similar", "choose_any"}
     *             ),
     *             @OA\Property(property="allow_submission_files", type="boolean", example=true),
     *             @OA\Property(property="due_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59Z"),
     *             @OA\Property(property="is_published", type="boolean", example=false),
     *             @OA\Property(
     *                 property="questions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="question_type", type="string", enum={"essay", "short_answer", "creative_writing", "argumentative", "descriptive", "narrative"}, example="essay"),
     *                     @OA\Property(property="question_text", type="string", example="Write an essay about climate change"),
     *                     @OA\Property(property="instructions", type="string", example="Include introduction, body, and conclusion"),
     *                     @OA\Property(property="word_limit", type="integer", example=500),
     *                     @OA\Property(property="points", type="number", example=25),
     *                     @OA\Property(property="rubric", type="string", example="Grading criteria..."),
     *                     @OA\Property(property="sample_answer", type="string", example="Sample response...")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="classroom_assignments",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="classroom_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="due_date", type="string", format="date-time", example="2024-12-31T23:59:59Z")
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
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{id}/update",
     *     tags={"Writing Tasks"},
     *     summary="Update writing task",
     *     description="Update an existing writing task details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Updated Essay Writing Task"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="instructions", type="string", example="Updated instructions"),
     *                 @OA\Property(property="sample_answer", type="string", example="Updated sample answer"),
     *                 @OA\Property(property="word_limit", type="integer", example=750, nullable=true),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *                 @OA\Property(property="timer_type", type="string", enum={"none", "countdown", "countup"}, example="countdown"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", example=5400, nullable=true),
     *                 @OA\Property(property="allow_retake", type="boolean", example=false),
     *                 @OA\Property(property="max_retake_attempts", type="integer", example=2, nullable=true),
     *                 @OA\Property(
     *                     property="retake_options",
     *                     type="string",
     *                     description="JSON string of retake options array",
     *                     example="[""rewrite_all"", ""group_similar""]"
     *                 ),
     *                 @OA\Property(property="allow_submission_files", type="boolean", example=false),
     *                 @OA\Property(property="due_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59Z"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="string",
     *                     description="JSON string of updated questions array",
     *                     example="[{""question_type"":""essay"",""question_text"":""Updated essay question"",""word_limit"":600,""points"":30}]"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload updated reference materials"
     *                 ),
     *                 @OA\Property(
     *                     property="classroom_assignments",
     *                     type="string",
     *                     description="JSON string of updated classroom assignments",
     *                     example="[{""classroom_id"":""uuid"",""due_date"":""2024-12-31T23:59:59Z""}]"
     *                 )
     *             )
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