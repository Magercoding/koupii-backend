<?php

namespace App\Swagger\V1\WritingTask;

use OpenApi\Annotations as OA;

class WritingReviewDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/writing-tasks/{taskId}/submissions/{submissionId}/review",
     *     tags={"Writing Reviews"},
     *     summary="Review a student submission",
     *     description="Provide feedback and score for a student's writing submission",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         description="Writing task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Writing submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", minimum=0, maximum=100, example=85),
     *             @OA\Property(property="comments", type="string", example="Good structure but needs improvement in grammar"),
     *             @OA\Property(
     *                 property="detailed_feedback",
     *                 type="object",
     *                 @OA\Property(property="grammar", type="integer", minimum=0, maximum=100, example=75),
     *                 @OA\Property(property="vocabulary", type="integer", minimum=0, maximum=100, example=90),
     *                 @OA\Property(property="structure", type="integer", minimum=0, maximum=100, example=85),
     *                 @OA\Property(property="content", type="integer", minimum=0, maximum=100, example=80)
     *             ),
     *             @OA\Property(
     *                 property="corrections",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="original", type="string", example="They was happy"),
     *                     @OA\Property(property="corrected", type="string", example="They were happy"),
     *                     @OA\Property(property="type", type="string", example="grammar"),
     *                     @OA\Property(property="explanation", type="string", example="Subject-verb agreement error")
     *                 )
     *             ),
     *             @OA\Property(property="suggestions", type="string", example="Try to vary your sentence structure more")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission reviewed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission reviewed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submission",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="status", type="string", example="reviewed"),
     *                     @OA\Property(property="score", type="integer", example=85)
     *                 ),
     *                 @OA\Property(
     *                     property="review",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="score", type="integer", example=85),
     *                     @OA\Property(property="comments", type="string"),
     *                     @OA\Property(property="reviewed_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=500, description="Failed to review submission")
     * )
     */
    public function review()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-reviews/pending",
     *     tags={"Writing Reviews"},
     *     summary="Get pending reviews",
     *     description="Retrieve submissions that need to be reviewed by the teacher",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pending reviews retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Pending reviews retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="writing_task_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_id", type="string", format="uuid"),
     *                     @OA\Property(property="status", type="string", example="submitted"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                     @OA\Property(property="time_taken_seconds", type="integer"),
     *                     @OA\Property(
     *                         property="student",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="writingTask",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="difficulty", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getPendingReviews()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-reviews/bulk",
     *     tags={"Writing Reviews"},
     *     summary="Bulk review submissions",
     *     description="Review multiple submissions at once",
     *     security={{"sanctum":{}}},
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
     *             @OA\Property(
     *                 property="reviews",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="submission_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *                     @OA\Property(property="score", type="integer", minimum=0, maximum=100, example=85),
     *                     @OA\Property(property="comments", type="string", example="Good work overall")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk review completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bulk review completed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="successful_reviews", type="integer", example=5),
     *                 @OA\Property(property="failed_reviews", type="integer", example=0),
     *                 @OA\Property(
     *                     property="results",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="submission_id", type="string", format="uuid"),
     *                         @OA\Property(property="status", type="string", enum={"success", "failed"}),
     *                         @OA\Property(property="message", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Failed to complete bulk review")
     * )
     */
    public function bulkReview()
    {
    }
}