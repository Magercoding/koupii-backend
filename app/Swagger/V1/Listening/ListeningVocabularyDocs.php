<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningVocabularyDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/vocabulary",
     *     tags={"Listening Vocabulary"},
     *     summary="Get vocabulary from listening tasks",
     *     description="Retrieve vocabulary words discovered or learned from listening tasks",
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
     *         name="difficulty",
     *         in="query",
     *         required=false,
     *         description="Filter by difficulty level",
     *         @OA\Schema(type="string", enum={"beginner", "intermediate", "advanced"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening vocabulary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening vocabulary retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="word", type="string"),
     *                     @OA\Property(property="definition", type="string"),
     *                     @OA\Property(property="pronunciation", type="string"),
     *                     @OA\Property(property="part_of_speech", type="string"),
     *                     @OA\Property(property="difficulty_level", type="string"),
     *                     @OA\Property(property="context_sentence", type="string"),
     *                     @OA\Property(property="audio_timestamp", type="integer"),
     *                     @OA\Property(property="frequency_count", type="integer"),
     *                     @OA\Property(
     *                         property="task",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string")
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
     *     path="/api/v1/listening/vocabulary",
     *     tags={"Listening Vocabulary"},
     *     summary="Add vocabulary from listening",
     *     description="Add or discover new vocabulary from a listening task",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"task_id", "word"},
     *             @OA\Property(property="task_id", type="string", format="uuid"),
     *             @OA\Property(property="word", type="string"),
     *             @OA\Property(property="definition", type="string"),
     *             @OA\Property(property="pronunciation", type="string"),
     *             @OA\Property(property="part_of_speech", type="string"),
     *             @OA\Property(property="context_sentence", type="string"),
     *             @OA\Property(property="audio_timestamp", type="integer"),
     *             @OA\Property(property="difficulty_level", type="string", enum={"beginner", "intermediate", "advanced"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vocabulary added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary added successfully"),
     *             @OA\Property(property="vocabulary_id", type="string", format="uuid")
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
     *     path="/api/v1/listening/vocabulary/{id}",
     *     tags={"Listening Vocabulary"},
     *     summary="Get specific vocabulary entry",
     *     description="Retrieve detailed information about a specific vocabulary entry",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vocabulary ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vocabulary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="word", type="string"),
     *             @OA\Property(property="definition", type="string"),
     *             @OA\Property(property="pronunciation", type="string"),
     *             @OA\Property(property="part_of_speech", type="string"),
     *             @OA\Property(property="difficulty_level", type="string"),
     *             @OA\Property(property="context_sentence", type="string"),
     *             @OA\Property(property="audio_timestamp", type="integer"),
     *             @OA\Property(property="frequency_count", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="related_words",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vocabulary not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/listening/vocabulary/{id}",
     *     tags={"Listening Vocabulary"},
     *     summary="Remove vocabulary entry",
     *     description="Remove a vocabulary entry from listening vocabulary bank",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vocabulary ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vocabulary removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary removed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vocabulary not found"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy()
    {
    }
}