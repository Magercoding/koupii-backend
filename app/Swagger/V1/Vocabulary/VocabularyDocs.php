<?php

namespace App\Swagger\V1\Vocabulary;

use OpenApi\Annotations as OA;
class VocabularyDocs
{
  /**
     * @OA\Get(
     *     path="/api/vocab/vocabularies",
     *     tags={"Vocabulary"},
     *     summary="Get all vocabularies",
     *     description="Returns a list of all vocabulary entries.",
     *     security={{"bearerAuth":{}}},
*     @OA\Response(
     *         response=200,
     *         description="List of vocabulary entries",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", example="uuid-string"),
     *                 @OA\Property(property="teacher_id", type="string", example="uuid-teacher"),
     *                 @OA\Property(property="category_id", type="string", example="uuid-category"),
     *                 @OA\Property(property="word", type="string", example="Elephant"),
     *                 @OA\Property(property="translation", type="string", example="Gajah"),
     *                 @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *                 @OA\Property(property="explanation", type="string", example="A large mammal with trunk."),
     *                 @OA\Property(property="audio_file_path", type="string", example="/storage/audio/688cb485b0b72.mp3"),
     *                 @OA\Property(property="is_public", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", example="2025-08-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-08-01T10:00:00Z"),
     *                 @OA\Property(property="is_bookmarked", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="teacher",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="571bd78d-4879-44e0-9697-05b6e8bebc5d"),
     *                     @OA\Property(property="name", type="string", example="Teacher User 1"),
     *                ),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="01985aba-6229-7398-8eb3-aec300a712a0"),
     *                     @OA\Property(property="name", type="string", example="Noun"),
     *                     @OA\Property(property="color_code", type="string", example="#FFC107"),
     *                )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        
    }

    /**
     * @OA\Post(
     *     path="/api/vocab/create",
     *     tags={"Vocabulary"},
     *     summary="Create a new vocabulary entry",
     *     description="Adds a new vocabulary entry to the system.",
     *     security={{"bearerAuth":{}}},
*     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id","word","translation","is_public"},
     *                 @OA\Property(property="category_id", type="string", example="uuid-category"),
     *                 @OA\Property(property="word", type="string", example="Elephant"),
     *                 @OA\Property(property="translation", type="string", example="Gajah"),
     *                 @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *                 @OA\Property(property="explanation", type="string", example="A large mammal with trunk."),
     *                 @OA\Property(property="audio_file_path", type="file", example="elephant.mp3"),
     *                 @OA\Property(property="is_public", type="boolean", example=1)
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vocabulary created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="uuid-string"),
     *                 @OA\Property(property="teacher_id", type="string", example="uuid-teacher"),
     *                 @OA\Property(property="category_id", type="string", example="uuid-category"),
     *                 @OA\Property(property="word", type="string", example="Elephant"),
     *                 @OA\Property(property="translation", type="string", example="Gajah"),
     *                 @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *                 @OA\Property(property="explanation", type="string", example="A large mammal with trunk."),
     *                 @OA\Property(property="audio_file_path", type="string", example="/storage/audio/688cb485b0b72.mp3"),
     *                 @OA\Property(property="is_public", type="boolean", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2025-08-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-08-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/vocab/{id}",
     *     tags={"Vocabulary"},
     *     summary="Get a specific vocabulary entry",
     *     description="Retrieve details of a vocabulary entry by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the vocabulary",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
*     @OA\Response(
     *         response=200,
     *         description="Vocabulary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid-string"),
     *             @OA\Property(property="teacher_id", type="string", example="uuid-teacher"),
     *             @OA\Property(property="category_id", type="string", example="uuid-category"),
     *             @OA\Property(property="word", type="string", example="Elephant"),
     *             @OA\Property(property="translation", type="string", example="Gajah"),
     *             @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *             @OA\Property(property="explanation", type="string", example="A large mammal with trunk."),
     *             @OA\Property(property="audio_file_path", type="string", example="/storage/audio/688cb485b0b72.mp3"),
     *             @OA\Property(property="is_public", type="boolean", example=1),
     *             @OA\Property(property="created_at", type="string", example="2025-08-01T10:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", example="2025-08-01T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vocabulary not found")
     * )
     */
    public function show()
    {
       
    }

    /**
     * @OA\Post(
     *     path="/api/vocab/update/{id}",
     *     tags={"Vocabulary"},
     *     summary="Update a vocabulary entry",
     *     description="Updates an existing vocabulary entry.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the vocabulary",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
*     @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         required=true,
     *         description="Override HTTP method for PATCH requests",
     *         @OA\Schema(type="string", example="PATCH")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="category_id", type="string", example="uuid-category"),
     *                 @OA\Property(property="word", type="string", example="Elephant Updated"),
     *                 @OA\Property(property="translation", type="string", example="Gajah Besar"),
     *                 @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *                 @OA\Property(property="explanation", type="string", example="Updated explanation"),
     *                 @OA\Property(property="audio_file_path", type="file", example="elephant-new.mp3"),
     *                 @OA\Property(property="is_public", type="boolean", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vocabulary updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="uuid-string"),
     *                 @OA\Property(property="teacher_id", type="string", example="uuid-teacher"),
     *                 @OA\Property(property="category_id", type="string", example="uuid-category"),
     *                 @OA\Property(property="word", type="string", example="Elephant Updated"),
     *                 @OA\Property(property="translation", type="string", example="Gajah Besar"),
     *                 @OA\Property(property="spelling", type="string", example="ˈeləfənt"),
     *                 @OA\Property(property="explanation", type="string", example="Updated explanation"),
     *                 @OA\Property(property="audio_file_path", type="string", example="/storage/audio/688cb485b0b72.mp3"),
     *                 @OA\Property(property="is_public", type="boolean", example=0),
     *                 @OA\Property(property="created_at", type="string", example="2025-08-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-08-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vocabulary not found"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update()
    {
     
    }

    /**
     * @OA\Delete(
     *     path="/api/vocab/delete/{id}",
     *     tags={"Vocabulary"},
     *     summary="Delete a vocabulary entry",
     *     description="Deletes a vocabulary entry by its UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID of the vocabulary",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
*     @OA\Response(
     *         response=200,
     *         description="Vocabulary deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vocabulary not found")
     * )
     */
    public function destroy()
    {
     
    }

    /**
     * @OA\Post(
     *     path="/api/vocab/{id}/bookmark",
     *     tags={"Vocabulary"},
     *     summary="Toggle vocabulary bookmark",
     *     description="Toggle the bookmark status of a vocabulary entry for the authenticated user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the vocabulary",
     *         @OA\Schema(type="string", example="123")
     *     ),
*     @OA\Response(
     *         response=200,
     *         description="Bookmark toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vocabulary bookmarked.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vocabulary not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Vocabulary not found")
     *         )
     *     )
     * )
     */
    public function toggleBookmark()
    {
      
    }
}

