<?php

namespace App\Swagger\V1\Test;

use OpenApi\Annotations as OA;

class TestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/tests",
     *     tags={"Tests"},
     *     summary="Get list of tests",
     *     description="Retrieve paginated list of tests created by the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by test type",
     *         @OA\Schema(type="string", enum={"reading", "listening", "speaking", "writing"})
     *     ),
     *     @OA\Parameter(
     *         name="difficulty",
     *         in="query",
     *         required=false,
     *         description="Filter by difficulty level",
     *         @OA\Schema(type="string", enum={"beginner", "intermediate", "advanced"})
     *     ),
     *     @OA\Parameter(
     *         name="is_published",
     *         in="query",
     *         required=false,
     *         description="Filter by published status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by test title",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page (default: 15)",
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="is_published", type="boolean"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="count", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total_pages", type="integer")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/tests",
     *     tags={"Tests"},
     *     summary="Create a new test",
     *     description="Create a comprehensive test with passages, question groups, and questions",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "type", "difficulty"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="IELTS Reading Practice Test"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="Comprehensive reading test with 3 passages"),
     *             @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}, example="reading"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="test_type", type="string", enum={"single", "final"}, example="single"),
     *             @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}, example="countdown"),
     *             @OA\Property(property="timer_settings", type="object", example={"time_limit": 3600}),
     *             @OA\Property(property="allow_repetition", type="boolean", example=false),
     *             @OA\Property(property="max_repetition_count", type="integer", minimum=0, maximum=10, example=2),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="settings", type="object", example={"shuffle_questions": false}),
     *             @OA\Property(
     *                 property="passages",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="title", type="string", example="Passage 1: Climate Change"),
     *                     @OA\Property(property="description", type="string", example="Reading passage about climate change effects"),
     *                     @OA\Property(property="audio_file_path", type="string", nullable=true, example="/audio/passage1.mp3"),
     *                     @OA\Property(property="transcript_type", type="string", enum={"descriptive", "conversation"}, nullable=true),
     *                     @OA\Property(property="transcript", type="object", nullable=true),
     *                     @OA\Property(
     *                         property="question_groups",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="instruction", type="string", example="Choose the correct answer"),
     *                             @OA\Property(
     *                                 property="questions",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="question_type", type="string", example="multiple_choice"),
     *                                     @OA\Property(property="question_number", type="number", example=1),
     *                                     @OA\Property(property="question_text", type="string", example="What is the main cause of climate change?"),
     *                                     @OA\Property(property="question_data", type="object", nullable=true),
                                     @OA\Property(property="correct_answers", type="object"),
     *                                     @OA\Property(property="points_value", type="number", example=1),
     *                                     @OA\Property(
     *                                         property="options",
     *                                         type="array",
     *                                         @OA\Items(
     *                                             type="object",
     *                                             @OA\Property(property="option_key", type="string", example="A"),
     *                                             @OA\Property(property="option_text", type="string", example="Greenhouse gases")
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="difficulty", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/tests/{id}",
     *     tags={"Tests"},
     *     summary="Get test details",
     *     description="Retrieve detailed information about a specific test including all questions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="difficulty", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/v1/tests/{id}",
     *     tags={"Tests"},
     *     summary="Update test",
     *     description="Update test information (only creator can update)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", maxLength=255, example="Updated Test Title"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="Updated description"),
     *             @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *             @OA\Property(property="test_type", type="string", enum={"single", "final"}),
     *             @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}),
     *             @OA\Property(property="timer_settings", type="object"),
     *             @OA\Property(property="allow_repetition", type="boolean"),
     *             @OA\Property(property="max_repetition_count", type="integer", minimum=0, maximum=10),
     *             @OA\Property(property="is_public", type="boolean"),
     *             @OA\Property(property="is_published", type="boolean"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="difficulty", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized to update this test"),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/tests/{id}",
     *     tags={"Tests"},
     *     summary="Delete test",
     *     description="Delete a test (only creator can delete)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized to delete this test"),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Post(
     *     path="/api/v1/tests/{id}/duplicate",
     *     tags={"Tests"},
     *     summary="Duplicate test",
     *     description="Create a copy of an existing test with all its content",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID to duplicate",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test duplicated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Test duplicated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="difficulty", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function duplicate() {}
}