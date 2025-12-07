<?php

namespace App\Swagger\V1\WritingTest;

use OpenApi\Annotations as OA;

class WritingTestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/writing-tests",
     *     tags={"Writing Tests"},
     *     summary="Get all writing tests",
     *     description="Retrieve writing tests based on user role. Admin sees all tests, students see only published tests, teachers see only their own tests.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search in title and description",
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
     *         name="is_published",
     *         in="query",
     *         required=false,
     *         description="Filter by published status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Writing tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing tests retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/WritingTest")
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
     *     path="/api/v1/writing-tests",
     *     tags={"Writing Tests"},
     *     summary="Create a new writing test",
     *     description="Create a new writing test with prompts and evaluation criteria. Only admins and teachers can create tests.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreWritingTestRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Writing test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/WritingTest")
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
     *     path="/api/v1/writing-tests/{id}",
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
     *     @OA\Response(
     *         response=200,
     *         description="Test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/WritingTest")
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
     * @OA\Put(
     *     path="/api/v1/writing-tests/{id}",
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateWritingTestRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/WritingTest")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/writing-tests/{id}",
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
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function destroy()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/writing-tests/search",
     *     tags={"Writing Tests"},
     *     summary="Search writing tests",
     *     description="Search and filter writing tests with advanced criteria",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="title", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="difficulty", in="query", required=false, @OA\Schema(type="string", enum={"beginner", "intermediate", "advanced"})),
     *     @OA\Parameter(name="test_type", in="query", required=false, @OA\Schema(type="string", enum={"academic", "general"})),
     *     @OA\Parameter(name="creator_id", in="query", required=false, @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="is_published", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search results retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WritingTest"))
     *         )
     *     )
     * )
     */
    public function search()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/writing-tests/{id}/duplicate",
     *     tags={"Writing Tests"},
     *     summary="Duplicate a writing test",
     *     description="Create a copy of an existing writing test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID to duplicate",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Copy of Original Test"),
     *             @OA\Property(property="description", type="string", example="Modified description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test duplicated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Writing test duplicated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/WritingTest")
     *         )
     *     )
     * )
     */
    public function duplicate()
    {
    }
}