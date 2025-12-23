<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingReviewDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading/reviews",
     *     tags={"Reading Reviews"},
     *     summary="Get reading test reviews",
     *     description="Retrieve reviews for reading test submissions",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="test_id",
     *         in="query",
     *         required=false,
     *         description="Filter by test ID",
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
     *         description="Reading reviews retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading reviews retrieved successfully"),
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
     *                         @OA\Property(property="test_title", type="string"),
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
     *     path="/api/v1/reading/reviews",
     *     tags={"Reading Reviews"},
     *     summary="Create a reading review",
     *     description="Create a review for a reading test submission",
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
     *             @OA\Property(property="suggestions", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading review created successfully"),
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
     *     path="/api/v1/reading/reviews/{id}",
     *     tags={"Reading Reviews"},
     *     summary="Get a specific reading review",
     *     description="Retrieve detailed information about a specific reading review",
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
     *         description="Reading review retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="submission_id", type="string", format="uuid"),
     *             @OA\Property(property="reviewer_id", type="string", format="uuid"),
     *             @OA\Property(property="reviewer_name", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="detailed_feedback", type="object"),
     *             @OA\Property(property="suggestions", type="string"),
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

    /**
     * @OA\Put(
     *     path="/api/v1/reading/reviews/{id}",
     *     tags={"Reading Reviews"},
     *     summary="Update a reading review",
     *     description="Update an existing reading review",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Review ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", minimum=0, maximum=100),
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="detailed_feedback", type="object"),
     *             @OA\Property(property="suggestions", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading review updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading review updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Review not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update()
    {
    }
}