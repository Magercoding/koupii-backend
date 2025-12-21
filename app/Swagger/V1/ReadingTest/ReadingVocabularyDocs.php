<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingVocabularyDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading/submissions/{submissionId}/vocabularies",
     *     tags={"Reading Vocabulary"},
     *     summary="Get discovered vocabularies",
     *     description="Retrieve vocabularies discovered from a completed reading test",
     *     security={{"bearerAuth":{}}},
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
     *         description="Discovered vocabularies retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="vocabulary_id", type="string", format="uuid"),
     *                     @OA\Property(property="discovered_at", type="string", format="date-time"),
     *                     @OA\Property(property="is_saved", type="boolean"),
     *                     @OA\Property(
     *                         property="vocabulary",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="word", type="string"),
     *                         @OA\Property(property="translation", type="string"),
     *                         @OA\Property(property="spelling", type="string"),
     *                         @OA\Property(property="explanation", type="string"),
     *                         @OA\Property(property="audio_url", type="string", nullable=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Not your submission")
     * )
     */
    public function getDiscoveredVocabularies()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reading/vocabulary/save",
     *     tags={"Reading Vocabulary"},
     *     summary="Save vocabulary to personal bank",
     *     description="Save a discovered vocabulary word to the student's personal vocabulary bank",
     *     security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="vocabulary_id", type="string", format="uuid", description="Vocabulary ID to save"),
     *             @OA\Property(property="test_id", type="string", format="uuid", description="Test where vocabulary was discovered")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vocabulary saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vocabulary saved to your bank"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="vocabulary_id", type="string", format="uuid"),
     *                 @OA\Property(property="is_mastered", type="boolean"),
     *                 @OA\Property(property="practice_count", type="integer"),
     *                 @OA\Property(property="mastery_level", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Vocabulary not discovered or already saved"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function saveVocabulary()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reading/vocabulary/bank",
     *     tags={"Reading Vocabulary"},
     *     summary="Get student's vocabulary bank",
     *     description="Retrieve the student's personal vocabulary bank with filtering options",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="mastery_level",
     *         in="query",
     *         description="Filter by mastery level",
     *         @OA\Schema(type="string", enum={"new", "beginner", "advanced", "mastered"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in word or translation",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vocabulary bank retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="vocabulary_id", type="string", format="uuid"),
     *                         @OA\Property(property="is_mastered", type="boolean"),
     *                         @OA\Property(property="practice_count", type="integer"),
     *                         @OA\Property(property="mastery_level", type="string"),
     *                         @OA\Property(property="last_practiced_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(
     *                             property="vocabulary",
     *                             type="object",
     *                             @OA\Property(property="word", type="string"),
     *                             @OA\Property(property="translation", type="string"),
     *                             @OA\Property(property="explanation", type="string"),
     *                             @OA\Property(property="audio_url", type="string", nullable=true)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getVocabularyBank()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reading/vocabulary/practice",
     *     tags={"Reading Vocabulary"},
     *     summary="Practice vocabulary",
     *     description="Record a vocabulary practice session",
     *     security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="vocabulary_id", type="string", format="uuid", description="Vocabulary ID to practice")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Practice session recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Practice session recorded")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Vocabulary not in your bank"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function practiceVocabulary()
    {
    }
}

