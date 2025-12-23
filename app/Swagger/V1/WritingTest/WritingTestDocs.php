<?php

namespace App\Swagger\V1\WritingTest;

use OpenApi\Annotations as OA;

class WritingTestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/writing/tests",
     *     tags={"Writing Tests"},
     *     summary="Get all writing tests",
     *     description="Retrieve writing tests based on user role. Admin sees all tests, students see only published tests, teachers see only their own tests.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Writing tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing tests retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="creator_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                     @OA\Property(property="creator_name", type="string", example="Teacher Name"),
     *                     @OA\Property(property="type", type="string", example="writing"),
     *                     @OA\Property(property="test_type", type="string", example="academic"),
     *                     @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                     @OA\Property(property="title", type="string", example="IELTS Writing Task 1"),
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
     *                     @OA\Property(property="statistics", type="object",
     *                         @OA\Property(property="total_attempts", type="integer", example=15),
     *                         @OA\Property(property="completed_attempts", type="integer", example=12),
     *                         @OA\Property(property="average_score", type="number", example=85.5)
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
     *     path="/api/v1/writing/tests",
     *     tags={"Writing Tests"},
     *     summary="Create a new writing test",
     *     description="Create a new writing test with prompts and settings.",
     *     security={{"bearerAuth":{}}},
     *   
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "test_type", "difficulty"},
     *             @OA\Property(property="title", type="string", example="IELTS Writing Test"),
     *             @OA\Property(property="description", type="string", example="A comprehensive writing test"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general", "business", "ielts", "toefl"}, example="ielts"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="timer_mode", type="string", enum={"none", "test", "practice"}, example="test"),
     *             @OA\Property(property="timer_settings", type="object", 
     *                 @OA\Property(property="test_time", type="integer", example=60),
     *                 @OA\Property(property="warning_time", type="integer", example=5)
     *             ),
     *             @OA\Property(property="allow_repetition", type="boolean", example=true),
     *             @OA\Property(property="max_repetition_count", type="integer", example=3),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="settings", type="object",
     *                 @OA\Property(property="instructions", type="string", example="Write your response clearly and coherently"),
     *                 @OA\Property(property="sample_format", type="string", example="Essay format"),
     *                 @OA\Property(property="word_limit", type="integer", example=250),
     *                 @OA\Property(property="cover_image", type="string", example="test_cover.jpg"),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Writing test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test created successfully"),
     *             @OA\Property(property="test_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing/tests/{id}",
     *     tags={"Writing Tests"},
     *     summary="Get a specific writing test",
     *     description="Retrieve detailed information about a specific writing test including prompts.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing test details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="creator_id", type="string", format="uuid"),
     *             @OA\Property(property="creator_name", type="string"),
     *             @OA\Property(property="type", type="string", example="writing"),
     *             @OA\Property(property="test_type", type="string"),
     *             @OA\Property(property="difficulty", type="string"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="timer_mode", type="string"),
     *             @OA\Property(property="timer_settings", type="object"),
     *             @OA\Property(property="allow_repetition", type="boolean"),
     *             @OA\Property(property="max_repetition_count", type="integer"),
     *             @OA\Property(property="is_public", type="boolean"),
     *             @OA\Property(property="is_published", type="boolean"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/writing/tests/{id}",
     *     tags={"Writing Tests"},
     *     summary="Update a writing test",
     *     description="Update an existing writing test. Only test creator or admin can update.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated IELTS Writing Test"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general", "business", "ielts", "toefl"}),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="timer_mode", type="string", enum={"none", "test", "practice"}),
     *             @OA\Property(property="timer_settings", type="object"),
     *             @OA\Property(property="allow_repetition", type="boolean"),
     *             @OA\Property(property="max_repetition_count", type="integer"),
     *             @OA\Property(property="is_public", type="boolean"),
     *             @OA\Property(property="is_published", type="boolean"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test updated successfully"),
     *             @OA\Property(property="test_id", type="string", format="uuid"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/writing/tests/{id}",
     *     tags={"Writing Tests"},
     *     summary="Delete a writing test",
     *     description="Delete a writing test and all related data. Only test creator or admin can delete.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy()
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/writing/tests/{id}/toggle-publish",
     *     tags={"Writing Tests"},
     *     summary="Toggle publish status of a writing test",
     *     description="Toggle the published status of a writing test. Only test creator or admin can perform this action.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Writing test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Publish status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test published successfully"),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function togglePublish()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing/tests/search",
     *     tags={"Writing Tests"},
     *     summary="Search writing tests",
     *     description="Search writing tests based on various criteria.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="title",
     *         in="query",
     *         required=false,
     *         description="Search by test title",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="difficulty",
     *         in="query",
     *         required=false,
     *         description="Filter by difficulty level",
     *         @OA\Schema(type="string", enum={"beginner", "intermediate", "advanced"})
     *     ),
     *     @OA\Parameter(
     *         name="test_type",
     *         in="query",
     *         required=false,
     *         description="Filter by test type",
     *         @OA\Schema(type="string", enum={"academic", "general", "business", "ielts", "toefl"})
     *     ),
     *     @OA\Parameter(
     *         name="creator_id",
     *         in="query",
     *         required=false,
     *         description="Filter by creator ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="is_published",
     *         in="query",
     *         required=false,
     *         description="Filter by published status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search results retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function search()
    {
    }
}