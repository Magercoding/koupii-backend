<?php

namespace App\Swagger\V1\WritingTask;

use OpenApi\Annotations as OA;

class WritingSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/writing-tasks/{taskId}/submissions",
     *     tags={"Writing Submissions"},
     *     summary="Get submissions for a task",
     *     description="Retrieve all submissions for a specific writing task (Teacher view)",
     *     security={{"bearerAuth":{}}},
    
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="test_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_name", type="string"),
     *                     @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                     @OA\Property(property="score", type="integer", nullable=true),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{taskId}/submissions",
     *     tags={"Writing Submissions"},
     *     summary="Submit student writing",
     *     description="Submit a writing assignment (Student only)",
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"content"},
     *                 @OA\Property(property="content", type="string", example="My essay content goes here..."),
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Maximum 5 files, each up to 10MB"
     *                 ),
     *                 @OA\Property(property="time_taken_seconds", type="integer", example=1800)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Writing submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Task not found or unauthorized")
     * )
     */
    public function submit()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{taskId}/submissions/draft",
     *     tags={"Writing Submissions"},
     *     summary="Save draft",
     *     description="Auto-save draft functionality (Student only)",
     *     security={{"bearerAuth":{}}},

     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Draft content..."),
     *             @OA\Property(property="files", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="time_taken_seconds", type="integer", example=900)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Draft saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Draft saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function saveDraft()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{taskId}/submissions/retake",
     *     tags={"Writing Submissions"},
     *     summary="Create retake submission",
     *     description="Create a retake submission with specified retake option",
     *     security={{"bearerAuth":{}}},

     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"retake_option"},
     *             @OA\Property(property="retake_option", type="string", enum={"rewrite_all", "group_similar", "choose_any"}, example="rewrite_all"),
     *             @OA\Property(
     *                 property="chosen_mistakes",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Required when retake_option is 'choose_any'"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retake created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Retake created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="retake_option", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function createRetake()
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/writing-tasks/{taskId}/submissions/{submissionId}/done",
     *     tags={"Writing Submissions"},
     *     summary="Mark submission as done",
     *     description="Student acknowledges review and marks submission as done",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission marked as done successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission marked as done successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed", "done"}),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=400, description="Submission must be reviewed first")
     * )
     */
    public function markAsDone()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-tasks/{taskId}/submissions/{submissionId}",
     *     tags={"Writing Submissions"},
     *     summary="Get specific submission",
     *     description="Retrieve details of a specific submission",
     *     security={{"bearerAuth":{}}},

     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *                 @OA\Property(property="score", type="integer", nullable=true),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }
}

