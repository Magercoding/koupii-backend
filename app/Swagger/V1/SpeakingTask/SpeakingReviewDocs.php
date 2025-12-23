<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingReviewDocs
{
// ===== SPEAKING REVIEW ENDPOINTS =====

/**
 * @OA\Post(
 *     path="/speaking/reviews",
 *     tags={"Speaking Reviews"},
 *     summary="Submit speaking review",
 *     description="Submit a comprehensive review and grade for a speaking submission",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"submission_id", "total_score"},
 *             @OA\Property(property="submission_id", type="string", format="uuid", description="Submission UUID"),
 *             @OA\Property(property="total_score", type="number", minimum=0, maximum=100, description="Overall score"),
 *             @OA\Property(property="overall_feedback", type="string", maxLength=2000, description="General feedback"),
 *             @OA\Property(
 *                 property="grading_rubric",
 *                 type="object",
 *                 @OA\Property(property="fluency", type="number", minimum=0, maximum=25, description="Fluency score"),
 *                 @OA\Property(property="pronunciation", type="number", minimum=0, maximum=25, description="Pronunciation score"),
 *                 @OA\Property(property="vocabulary", type="number", minimum=0, maximum=25, description="Vocabulary score"),
 *                 @OA\Property(property="grammar", type="number", minimum=0, maximum=25, description="Grammar score")
 *             ),
 *             @OA\Property(
 *                 property="question_scores",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="question_id", type="string", format="uuid", description="Question UUID"),
 *                     @OA\Property(property="score", type="number", minimum=0, maximum=100, description="Question score"),
 *                     @OA\Property(property="comment", type="string", maxLength=1000, description="Question-specific feedback"),
 *                     @OA\Property(
 *                         property="rubric_scores",
 *                         type="object",
 *                         @OA\Property(property="fluency", type="number", minimum=0, maximum=25),
 *                         @OA\Property(property="pronunciation", type="number", minimum=0, maximum=25),
 *                         @OA\Property(property="vocabulary", type="number", minimum=0, maximum=25),
 *                         @OA\Property(property="grammar", type="number", minimum=0, maximum=25)
 *                     )
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="review_status", 
 *                 type="string", 
 *                 enum={"draft", "completed", "needs_revision"}, 
 *                 description="Review status"
 *             ),
 *             @OA\Property(property="review_notes", type="string", maxLength=1000, description="Internal review notes"),
 *             @OA\Property(property="time_spent_reviewing", type="integer", minimum=0, description="Review time in seconds"),
 *             @OA\Property(
 *                 property="recommendations",
 *                 type="object",
 *                 @OA\Property(
 *                     property="strengths", 
 *                     type="array", 
 *                     @OA\Items(type="string"), 
 *                     description="Student strengths",
 *                     example={"Clear pronunciation", "Good vocabulary usage", "Natural flow"}
 *                 ),
 *                 @OA\Property(
 *                     property="areas_for_improvement", 
 *                     type="array", 
 *                     @OA\Items(type="string"), 
 *                     description="Areas needing improvement",
 *                     example={"Grammar accuracy", "Response organization", "Speaking pace"}
 *                 ),
 *                 @OA\Property(property="next_steps", type="string", maxLength=1000, description="Recommended next steps")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Review submitted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReview"),
 *             @OA\Property(property="message", type="string", example="Review submitted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized to review this submission",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/speaking/reviews/{id}",
 *     tags={"Speaking Reviews"},
 *     summary="Get speaking review details",
 *     description="Retrieve detailed information about a specific speaking review",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Review UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReview"),
 *             @OA\Property(property="message", type="string", example="Review retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Review not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/speaking/reviews/{id}",
 *     tags={"Speaking Reviews"},
 *     summary="Update speaking review",
 *     description="Update an existing speaking review",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Review UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="total_score", type="number", minimum=0, maximum=100, description="Overall score"),
 *             @OA\Property(property="overall_feedback", type="string", maxLength=2000, description="General feedback"),
 *             @OA\Property(
 *                 property="grading_rubric",
 *                 type="object",
 *                 @OA\Property(property="fluency", type="number", minimum=0, maximum=25, description="Fluency score"),
 *                 @OA\Property(property="pronunciation", type="number", minimum=0, maximum=25, description="Pronunciation score"),
 *                 @OA\Property(property="vocabulary", type="number", minimum=0, maximum=25, description="Vocabulary score"),
 *                 @OA\Property(property="grammar", type="number", minimum=0, maximum=25, description="Grammar score")
 *             ),
 *             @OA\Property(
 *                 property="review_status", 
 *                 type="string", 
 *                 enum={"draft", "completed", "needs_revision"}, 
 *                 description="Review status"
 *             ),
 *             @OA\Property(property="review_notes", type="string", maxLength=1000, description="Internal review notes"),
 *             @OA\Property(
 *                 property="recommendations",
 *                 type="object",
 *                 @OA\Property(property="strengths", type="array", @OA\Items(type="string")),
 *                 @OA\Property(property="areas_for_improvement", type="array", @OA\Items(type="string")),
 *                 @OA\Property(property="next_steps", type="string", maxLength=1000)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Review updated successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReview"),
 *             @OA\Property(property="message", type="string", example="Review updated successfully")
 *         )
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
 *     path="/speaking/reviews",
 *     tags={"Speaking Reviews"},
 *     summary="Get teacher's speaking reviews",
 *     description="Retrieve paginated list of teacher's speaking reviews",
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
 *         name="review_status",
 *         in="query",
 *         description="Filter by review status",
 *         @OA\Schema(type="string", enum={"draft", "completed", "needs_revision"})
 *     ),
 *     @OA\Parameter(
 *         name="submission_id",
 *         in="query",
 *         description="Filter by submission",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="class_id",
 *         in="query",
 *         description="Filter by class",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/SpeakingReview")
 *                 ),
 *                 @OA\Property(property="total", type="integer", example=42)
 *             ),
 *             @OA\Property(property="message", type="string", example="Speaking reviews retrieved successfully")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/speaking/review-queue",
 *     tags={"Speaking Reviews"},
 *     summary="Get speaking review queue",
 *     description="Get list of speaking submissions awaiting review",
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
 *         name="class_id",
 *         in="query",
 *         description="Filter by class",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="task_id",
 *         in="query",
 *         description="Filter by task",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="priority",
 *         in="query",
 *         description="Filter by priority",
 *         @OA\Schema(type="string", enum={"high", "medium", "low"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="submission_id", type="string", format="uuid"),
 *                         @OA\Property(property="student_name", type="string", example="John Doe"),
 *                         @OA\Property(property="task_title", type="string", example="Speaking Assessment 1"),
 *                         @OA\Property(property="submitted_at", type="string", format="date-time"),
 *                         @OA\Property(property="priority", type="string", enum={"high", "medium", "low"}),
 *                         @OA\Property(property="class_name", type="string", example="English Advanced"),
 *                         @OA\Property(property="submission", ref="#/components/schemas/SpeakingSubmission")
 *                     )
 *                 ),
 *                 @OA\Property(property="total", type="integer", example=18)
 *             ),
 *             @OA\Property(property="message", type="string", example="Review queue retrieved successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized - Teachers only",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/speaking/reviews/{id}",
 *     tags={"Speaking Reviews"},
 *     summary="Delete speaking review",
 *     description="Delete a speaking review (draft reviews only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Review UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Review deleted successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Review not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Cannot delete completed review",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */

    /**
     * @OA\Get(
     *     path="/api/v1/speaking/reviews/{review}",
     *     summary="Get review details",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReviewResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Review not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/v1/speaking/reviews/{review}",
     *     summary="Update review",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="overall_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="pronunciation_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="fluency_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="grammar_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="vocabulary_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="content_score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="detailed_comments", type="string"),
     *             @OA\Property(property="strengths", type="string"),
     *             @OA\Property(property="areas_for_improvement", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReviewResource")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Review not found")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/speaking/reviews/{review}",
     *     summary="Delete review",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Review not found")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/speaking/reviews/{review}/publish",
     *     summary="Publish review to student",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review published successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review published to student"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReviewResource")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function publishReview() {}

    /**
     * @OA\Post(
     *     path="/api/v1/speaking/reviews/{review}/add-comment",
     *     summary="Add comment to review",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="Review ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="comment", type="string", example="Additional feedback for student")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SpeakingReviewResource")
     *         )
     *     )
     * )
     */
    public function addComment() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/speaking/bulk/review-submissions",
     *     summary="Bulk review multiple submissions",
     *     tags={"Speaking Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reviews", type="array", @OA\Items(
     *                 @OA\Property(property="submission_id", type="string", format="uuid"),
     *                 @OA\Property(property="overall_score", type="integer"),
     *                 @OA\Property(property="feedback", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk review completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reviewed 5 submissions successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reviewed", type="integer", example=5),
     *                 @OA\Property(property="failed", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - Teachers only")
     * )
     */
    public function bulkReview() {}
}
