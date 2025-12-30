<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingTestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading/tests",
     *     tags={"Reading Tests"},
     *     summary="Get all reading tests",
     *     description="Retrieve reading tests based on user role. Admin sees all tests, students see only published tests, teachers see only their own tests.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reading tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading tests retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="creator_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                     @OA\Property(property="creator_name", type="string", example="Teacher Name"),
     *                     @OA\Property(property="test_type", type="string", example="academic"),
     *                     @OA\Property(property="type", type="string", example="reading"),
     *                     @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                     @OA\Property(property="title", type="string", example="Reading Comprehension Test"),
     *                     @OA\Property(property="description", type="string", example="Test description"),
     *                     @OA\Property(property="timer_mode", type="string", example="test"),
     *                     @OA\Property(property="timer_settings", type="object"),
     *                     @OA\Property(property="allow_repetition", type="boolean", example=true),
     *                     @OA\Property(property="max_repetition_count", type="integer", example=3),
     *                     @OA\Property(property="is_public", type="boolean", example=false),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="settings", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="passages", type="array", @OA\Items(type="object"))
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
     *     path="/api/v1/reading/create",
     *     tags={"Reading Tests"},
     *     summary="Create a new reading test",
     *     description="Create a new reading test with passages and questions.",
     *     security={{"bearerAuth":{}}},
     *   
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "test_type", "difficulty", "passages"},
     *             @OA\Property(property="title", type="string", example="Reading Test 1"),
     *             @OA\Property(property="description", type="string", example="A comprehensive reading test"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general"}, example="academic"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="timer_mode", type="string", enum={"test", "passage", "question"}, example="test"),
     *             @OA\Property(property="timer_settings", type="object", example={"test_time": 60}),
     *             @OA\Property(property="allow_repetition", type="boolean", example=true),
     *             @OA\Property(property="max_repetition_count", type="integer", example=3),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="settings", type="object", example={}),
     *             @OA\Property(property="passages", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="title", type="string", example="Passage 1"),
     *                     @OA\Property(property="description", type="string", example="Passage description"),
     *                     @OA\Property(property="content", type="string", example="Passage content text...")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test created successfully"),
     *             @OA\Property(property="test_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reading/tests/{id}",
     *     tags={"Reading Tests"},
     *     summary="Get a specific reading test",
     *     description="Retrieve a specific reading test with all its passages and questions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="creator_id", type="string", format="uuid"),
     *             @OA\Property(property="creator_name", type="string", example="Teacher Name"),
     *             @OA\Property(property="title", type="string", example="Reading Test 1"),
     *             @OA\Property(property="description", type="string", example="Test description"),
     *             @OA\Property(property="test_type", type="string", example="academic"),
     *             @OA\Property(property="difficulty", type="string", example="intermediate"),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="passages", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="passage_id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="question_groups", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found or unauthorized access"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reading/update/{id}",
     *     tags={"Reading Tests"},
     *     summary="Update a reading test",
     *     description="Update an existing reading test. Only the creator or admin can update.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Reading Test"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general"}, example="academic"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *             @OA\Property(property="timer_mode", type="string", enum={"test", "passage", "question"}, example="test"),
     *             @OA\Property(property="timer_settings", type="object"),
     *             @OA\Property(property="allow_repetition", type="boolean", example=false),
     *             @OA\Property(property="max_repetition_count", type="integer", example=1),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test updated"),
     *             @OA\Property(property="test_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Update failed")
     * )
     */
    public function update()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reading/delete/passage/{id}",
     *     tags={"Reading Tests"},
     *     summary="Delete a passage",
     *     description="Delete a specific passage from a reading test.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Passage ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Passage deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Passage deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Passage not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deletePassage()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reading/delete/question/{id}",
     *     tags={"Reading Tests"},
     *     summary="Delete a question",
     *     description="Delete a specific question from a reading test. May also delete the question group if it becomes empty.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Question ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *  
     *     @OA\Response(
     *         response=200,
     *         description="Question deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Question deleted successfully"),
     *             @OA\Property(property="group_deleted", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Question not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deleteQuestion()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reading/delete/{id}",
     *     tags={"Reading Tests"},
     *     summary="Delete a reading test",
     *     description="Delete an entire reading test including all passages, questions, and associated images. Only the creator or admin can delete.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
  
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deleteTest()
    {
    }
}

