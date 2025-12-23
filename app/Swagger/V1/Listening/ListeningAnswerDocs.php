<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningAnswerDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/answers",
     *     tags={"Listening Answers"},
     *     summary="Get listening answers",
     *     description="Retrieve student answers for listening questions",
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
     *         name="submission_id",
     *         in="query",
     *         required=false,
     *         description="Filter by submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening answers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening answers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="question_id", type="string", format="uuid"),
     *                     @OA\Property(property="student_id", type="string", format="uuid"),
     *                     @OA\Property(property="submission_id", type="string", format="uuid"),
     *                     @OA\Property(property="answer_text", type="string"),
     *                     @OA\Property(property="is_correct", type="boolean"),
     *                     @OA\Property(property="points_earned", type="integer"),
     *                     @OA\Property(property="time_taken", type="integer"),
     *                     @OA\Property(
     *                         property="question",
     *                         type="object",
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="question_type", type="string"),
     *                         @OA\Property(property="correct_answer", type="string")
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
     *     path="/api/v1/listening/answers",
     *     tags={"Listening Answers"},
     *     summary="Submit a listening answer",
     *     description="Submit an answer for a specific listening question",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_id", "submission_id", "answer_text"},
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="submission_id", type="string", format="uuid"),
     *             @OA\Property(property="answer_text", type="string"),
     *             @OA\Property(property="time_taken", type="integer", description="Time taken in seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Answer submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Answer submitted successfully"),
     *             @OA\Property(property="answer_id", type="string", format="uuid"),
     *             @OA\Property(property="is_correct", type="boolean"),
     *             @OA\Property(property="points_earned", type="integer")
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
     *     path="/api/v1/listening/answers/{id}",
     *     tags={"Listening Answers"},
     *     summary="Get a specific listening answer",
     *     description="Retrieve detailed information about a specific listening answer",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Answer ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="student_id", type="string", format="uuid"),
     *             @OA\Property(property="submission_id", type="string", format="uuid"),
     *             @OA\Property(property="answer_text", type="string"),
     *             @OA\Property(property="is_correct", type="boolean"),
     *             @OA\Property(property="points_earned", type="integer"),
     *             @OA\Property(property="time_taken", type="integer"),
     *             @OA\Property(property="submitted_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Answer not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }
}