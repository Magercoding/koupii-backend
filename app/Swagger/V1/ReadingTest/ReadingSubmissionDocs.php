<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading/submissions",
     *     tags={"Reading Submissions"},
     *     summary="Get student's reading test submissions",
     *     description="Retrieve all reading test submissions for the authenticated student",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by submission status",
     *         @OA\Schema(type="string", enum={"in_progress", "submitted", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reading assignments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="test_id", type="string", format="uuid"),
     *                     @OA\Property(property="attempt_number", type="integer"),
     *                     @OA\Property(property="status", type="string", enum={"in_progress", "submitted", "completed"}),
     *                     @OA\Property(property="total_score", type="number", format="float"),
     *                     @OA\Property(property="percentage", type="number", format="float"),
     *                     @OA\Property(property="grade", type="string"),
     *                     @OA\Property(property="can_retake", type="boolean"),
     *                     @OA\Property(property="started_at", type="string", format="date-time"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true)
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
     *     path="/api/v1/reading/tests/{testId}/start",
     *     tags={"Reading Submissions"},
     *     summary="Start a new reading test attempt",
     *     description="Begin a new reading test attempt for the authenticated student",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="attempt_number", type="integer", minimum=1, maximum=10, description="Attempt number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading test started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reading test started successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="in_progress"),
     *                 @OA\Property(property="attempt_number", type="integer"),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Test cannot be attempted"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function start()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reading/submissions/{submissionId}",
     *     tags={"Reading Submissions"},
     *     summary="Get submission details",
     *     description="Retrieve detailed information about a specific submission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
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
     *         description="Submission details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="total_score", type="number", format="float"),
     *                 @OA\Property(property="percentage", type="number", format="float"),
     *                 @OA\Property(
     *                     property="answers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="question_id", type="string", format="uuid"),
     *                         @OA\Property(property="student_answer", type="string"),
     *                         @OA\Property(property="is_correct", type="boolean", nullable=true),
     *                         @OA\Property(property="points_earned", type="number", format="float")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Not your submission"),
     *     @OA\Response(response=404, description="Submission not found")
     * )
     */
    public function getSubmission()
    {
    }
}