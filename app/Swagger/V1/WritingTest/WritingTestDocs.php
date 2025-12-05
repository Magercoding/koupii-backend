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
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
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
     *                     @OA\Property(property="test_type", type="string", example="academic"),
     *                     @OA\Property(property="type", type="string", example="writing"),
     *                     @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                     @OA\Property(property="title", type="string", example="Essay Writing Test"),
     *                     @OA\Property(property="description", type="string", example="Test for academic essay writing skills"),
     *                     @OA\Property(property="timer_mode", type="string", example="test"),
     *                     @OA\Property(property="timer_settings", type="object", example={"test_time": 90}),
     *                     @OA\Property(property="allow_repetition", type="boolean", example=false),
     *                     @OA\Property(property="max_repetition_count", type="integer", example=1),
     *                     @OA\Property(property="is_public", type="boolean", example=false),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="settings", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="writing_prompts", type="array", @OA\Items(type="object"))
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
     *     description="Create a new writing test with prompts and evaluation criteria. Only admins and teachers can create tests.",
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
     *             required={"title", "description", "test_type", "difficulty", "writing_prompts"},
     *             @OA\Property(property="title", type="string", example="Academic Essay Writing Test"),
     *             @OA\Property(property="description", type="string", example="A comprehensive test for academic essay writing skills"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general"}, example="academic"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="timer_mode", type="string", enum={"test", "prompt"}, example="test"),
     *             @OA\Property(property="timer_settings", type="object", example={"test_time": 90}),
     *             @OA\Property(property="allow_repetition", type="boolean", example=false),
     *             @OA\Property(property="max_repetition_count", type="integer", example=1),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="settings", type="object", example={}),
     *             @OA\Property(
     *                 property="writing_prompts",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="title", type="string", example="Opinion Essay"),
     *                     @OA\Property(property="prompt_text", type="string", example="Some people believe that technology has made our lives easier. Others think it has made life more complicated. Discuss both views and give your opinion."),
     *                     @OA\Property(property="prompt_type", type="string", enum={"essay", "letter", "report", "review", "article", "proposal"}, example="essay"),
     *                     @OA\Property(property="word_limit", type="integer", example=250),
     *                     @OA\Property(property="time_limit", type="integer", example=40),
     *                     @OA\Property(property="instructions", type="string", example="Write a clear, well-structured essay with introduction, body paragraphs, and conclusion."),
     *                     @OA\Property(property="sample_answer", type="string", example="Sample essay response..."),
     *                     @OA\Property(
     *                         property="criteria",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="name", type="string", example="Content & Ideas"),
     *                             @OA\Property(property="description", type="string", example="Relevance, development, and support of ideas"),
     *                             @OA\Property(property="max_score", type="integer", example=9),
     *                             @OA\Property(property="weight", type="number", format="float", example=0.25),
     *                             @OA\Property(property="rubric", type="object", example={})
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Writing test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test created successfully"),
     *             @OA\Property(property="test_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Only admins and teachers can create tests")
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
     *     description="Retrieve a specific writing test with all its prompts and criteria.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="creator_id", type="string", format="uuid"),
     *             @OA\Property(property="creator_name", type="string", example="Teacher Name"),
     *             @OA\Property(property="title", type="string", example="Academic Essay Writing Test"),
     *             @OA\Property(property="description", type="string", example="Test description"),
     *             @OA\Property(property="test_type", type="string", example="academic"),
     *             @OA\Property(property="difficulty", type="string", example="intermediate"),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(
     *                 property="writing_prompts",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="Opinion Essay"),
     *                     @OA\Property(property="prompt_text", type="string", example="Prompt text..."),
     *                     @OA\Property(property="prompt_type", type="string", example="essay"),
     *                     @OA\Property(property="word_limit", type="integer", example=250),
     *                     @OA\Property(property="time_limit", type="integer", example=40),
     *                     @OA\Property(property="instructions", type="string", example="Instructions..."),
     *                     @OA\Property(property="sample_answer", type="string", example="Sample answer (hidden from students)"),
     *                     @OA\Property(
     *                         property="criteria",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="name", type="string", example="Content & Ideas"),
     *                             @OA\Property(property="description", type="string", example="Description..."),
     *                             @OA\Property(property="max_score", type="integer", example=9),
     *                             @OA\Property(property="weight", type="number", format="float", example=0.25),
     *                             @OA\Property(property="rubric", type="object")
     *                         )
     *                     )
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
     * @OA\Patch(
     *     path="/api/v1/writing/tests/{id}",
     *     tags={"Writing Tests"},
     *     summary="Update a writing test",
     *     description="Update an existing writing test. Only the creator or admin can update.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
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
     *             @OA\Property(property="title", type="string", example="Updated Essay Writing Test"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="test_type", type="string", enum={"academic", "general"}, example="academic"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *             @OA\Property(property="timer_mode", type="string", enum={"test", "prompt"}, example="prompt"),
     *             @OA\Property(property="timer_settings", type="object"),
     *             @OA\Property(property="allow_repetition", type="boolean", example=true),
     *             @OA\Property(property="max_repetition_count", type="integer", example=2),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="is_published", type="boolean", example=false),
     *             @OA\Property(property="settings", type="object"),
     *             @OA\Property(
     *                 property="writing_prompts",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", description="Optional - include for existing prompts"),
     *                     @OA\Property(property="title", type="string", example="Updated Prompt Title"),
     *                     @OA\Property(property="prompt_text", type="string", example="Updated prompt text..."),
     *                     @OA\Property(property="prompt_type", type="string", enum={"essay", "letter", "report", "review", "article", "proposal"}, example="letter"),
     *                     @OA\Property(property="word_limit", type="integer", example=300),
     *                     @OA\Property(property="time_limit", type="integer", example=30),
     *                     @OA\Property(property="instructions", type="string", example="Updated instructions..."),
     *                     @OA\Property(property="sample_answer", type="string", example="Updated sample answer...")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test updated successfully"),
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
     *     path="/api/v1/writing/tests/{id}",
     *     tags={"Writing Tests"},
     *     summary="Delete a writing test",
     *     description="Delete an entire writing test including all prompts and criteria. Only the creator or admin can delete.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/writing/prompts/{id}",
     *     tags={"Writing Tests"},
     *     summary="Delete a writing prompt",
     *     description="Delete a specific writing prompt from a test. Only the test creator or admin can delete.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Prompt ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Prompt deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing prompt deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Prompt not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deletePrompt()
    {
    }
}