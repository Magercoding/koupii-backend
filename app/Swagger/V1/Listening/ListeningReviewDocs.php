<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningReviewDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/reviews",
     *     tags={"Listening Reviews"},
     *     summary="Get listening reviews",
     *     description="Retrieve reviews for listening submissions",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="task_id",
     *         in="query",
     *         required=false,
     *         description="Filter by task ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         required=false,
     *         description="Filter by student ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening reviews retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening reviews retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="submission_id", type="string", format="uuid"),
     *                     @OA\Property(property="reviewer_id", type="string", format="uuid"),
     *                     @OA\Property(property="reviewer_name", type="string"),
     *                     @OA\Property(property="score", type="integer"),
     *                     @OA\Property(property="feedback", type="string"),
     *                     @OA\Property(property="reviewed_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="submission",
     *                         type="object",
     *                         @OA\Property(property="student_name", type="string"),
     *                         @OA\Property(property="task_title", type="string"),
     *                         @OA\Property(property="submitted_at", type="string", format="date-time")
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
     *     path="/api/v1/listening/reviews",
     *     tags={"Listening Reviews"},
     *     summary="Create a listening review",
     *     description="Create a review for a listening submission",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"submission_id", "score", "feedback"},
     *             @OA\Property(property="submission_id", type="string", format="uuid"),
     *             @OA\Property(property="score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="detailed_feedback", type="object"),
     *             @OA\Property(property="pronunciation_feedback", type="object"),
     *             @OA\Property(property="comprehension_feedback", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Listening review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening review created successfully"),
     *             @OA\Property(property="review_id", type="string", format="uuid")
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
     *     path="/api/v1/listening/reviews/{id}",
     *     tags={"Listening Reviews"},
     *     summary="Get a specific listening review",
     *     description="Retrieve detailed information about a specific listening review",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Review ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening review retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="submission_id", type="string", format="uuid"),
     *             @OA\Property(property="reviewer_id", type="string", format="uuid"),
     *             @OA\Property(property="reviewer_name", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="detailed_feedback", type="object"),
     *             @OA\Property(property="reviewed_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Review not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }
}