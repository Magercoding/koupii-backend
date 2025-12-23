<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningSubmissionDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions",
     *     tags={"Listening Submissions"},
     *     summary="Get listening submissions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/listening/submissions",
     *     tags={"Listening Submissions"},
     *     summary="Submit listening task",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Response(
     *         response=201,
     *         description="Submission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{id}",
     *     tags={"Listening Submissions"},
     *     summary="Get submission details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Submission retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found")
     * )
     */
    public function show() {}
}


