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
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"title", "type", "difficulty"},
     *                 @OA\Property(property="title", type="string", maxLength=255, example="IELTS Reading Practice Test"),
     *                 @OA\Property(property="description", type="string", maxLength=1000, example="Comprehensive reading test with 3 passages"),
     *                 @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}, example="reading"),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *                 @OA\Property(property="test_type", type="string", enum={"single", "final"}, example="single"),
     *                 @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}, example="countdown"),
     *                 @OA\Property(
     *                     property="timer_settings",
     *                     type="object",
     *                     description="Timer configuration settings",
     *                     @OA\Property(property="time_limit", type="integer", description="Time limit in seconds", example=3600),
     *                     @OA\Property(property="warning_time", type="integer", description="Warning time before end in seconds", example=300)
     *                 ),
     *                 @OA\Property(property="allow_repetition", type="boolean", example=false),
     *                 @OA\Property(property="max_repetition_count", type="integer", minimum=0, maximum=10, example=2),
     *                 @OA\Property(property="is_public", type="boolean", example=false),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="object",
     *                     description="Test configuration settings",
     *                     @OA\Property(property="shuffle_questions", type="boolean", description="Whether to shuffle questions", example=false),
     *                     @OA\Property(property="shuffle_options", type="boolean", description="Whether to shuffle answer options", example=false),
     *                     @OA\Property(property="show_results", type="boolean", description="Whether to show results after completion", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="passages",
     *                     type="array",
     *                     description="Array of reading/listening passages with questions",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="title", type="string", maxLength=255, example="Reading Passage 1"),
     *                         @OA\Property(property="description", type="string", example="Climate change article"),
     *                         @OA\Property(property="transcript_type", type="string", enum={"descriptive", "conversation"}, example="descriptive"),
     *                         @OA\Property(property="audio_file_path", type="string", description="Path to audio file for listening passages"),
     *                         @OA\Property(
     *                             property="question_groups",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="instruction", type="string", example="Answer the following questions"),
     *                                 @OA\Property(
     *                                     property="questions",
     *                                     type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="question_type", type="string", enum={"multiple_choice", "true_false", "fill_blank", "short_answer"}, example="multiple_choice"),
     *                                         @OA\Property(property="question_number", type="integer", example=1),
     *                                         @OA\Property(property="question_text", type="string", example="What is the main topic?"),
     *                                         @OA\Property(property="points_value", type="number", minimum=0, example=2),
     *                                         @OA\Property(
     *                                             property="correct_answers",
     *                                             type="array",
     *                                             @OA\Items(type="string"),
     *                                             example={"A"}
     *                                         ),
     *                                         @OA\Property(
     *                                             property="options",
     *                                             type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="option_key", type="string", example="A"),
     *                                                 @OA\Property(property="option_text", type="string", example="Climate")
     *                                             )
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Updated Test Title"),
     *                 @OA\Property(property="description", type="string", maxLength=1000, example="Updated description"),
     *                 @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                 @OA\Property(property="test_type", type="string", enum={"single", "final"}),
     *                 @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}),
     *                 @OA\Property(
     *                     property="timer_settings",
     *                     type="string",
     *                     description="JSON string of timer settings"
     *                 ),
     *                 @OA\Property(property="allow_repetition", type="boolean"),
     *                 @OA\Property(property="max_repetition_count", type="integer", minimum=0, maximum=10),
     *                 @OA\Property(property="is_public", type="boolean"),
     *                 @OA\Property(property="is_published", type="boolean"),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="string",
     *                     description="JSON string of test settings"
     *                 ),
     *                 @OA\Property(
     *                     property="passages",
     *                     type="string",
     *                     description="JSON string of passages array with questions",
     *                     example="[{""title"":""Updated Reading Passage"",""description"":""Updated content"",""transcript_type"":""descriptive"",""question_groups"":[{""instruction"":""Answer the updated questions"",""questions"":[{""question_type"":""multiple_choice"",""question_number"":1,""question_text"":""Updated question text?"",""points_value"":3,""options"":[{""option_key"":""A"",""option_text"":""Option A""},{""option_key"":""B"",""option_text"":""Option B""}],""correct_answers"":[""A""]}]}]}]"
     *                 ),
     *                 @OA\Property(
     *                     property="audio_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload new audio files for listening passages"
     *                 )
     *             )
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