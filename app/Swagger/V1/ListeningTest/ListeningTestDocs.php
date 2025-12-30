<?php

namespace App\Swagger\V1\ListeningTest;

use OpenApi\Annotations as OA;

class ListeningTestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/listening/tests",
     *     tags={"Listening Tests"},
     *     summary="Get all listening tests",
     *     description="Retrieve listening tests based on user role. Admin sees all tests, students see only published tests, teachers see only their own tests.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Listening tests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening tests retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="creator_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                     @OA\Property(property="creator_name", type="string", example="Teacher Name"),
     *                     @OA\Property(property="type", type="string", example="listening"),
     *                     @OA\Property(property="test_type", type="string", example="academic"),
     *                     @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                     @OA\Property(property="title", type="string", example="IELTS Listening Practice Test"),
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
     *     path="/api/v1/listening/tests",
     *     tags={"Listening Tests"},
     *     summary="Create a new listening test",
     *     description="Create a new listening test with audio segments and questions.",
     *     security={{"bearerAuth":{}}},
     *   
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description", "test_type", "difficulty"},
     *                 @OA\Property(property="title", type="string", example="IELTS Listening Test"),
     *                 @OA\Property(property="description", type="string", example="A comprehensive listening test"),
     *                 @OA\Property(property="test_type", type="string", enum={"academic", "general", "business", "ielts", "toefl"}, example="ielts"),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *                 @OA\Property(property="timer_mode", type="string", enum={"none", "test", "practice"}, example="test"),
     *                 @OA\Property(property="timer_settings", type="string", description="JSON object of timer settings", example="{""test_time"":60,""warning_time"":5}"),
     *                 @OA\Property(property="allow_repetition", type="boolean", example=true),
     *                 @OA\Property(property="max_repetition_count", type="integer", example=3),
     *                 @OA\Property(property="is_public", type="boolean", example=false),
     *                 @OA\Property(property="duration_minutes", type="integer", example=60),
     *                 @OA\Property(property="passing_score", type="integer", example=70),
     *                 @OA\Property(
     *                     property="audio_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload test audio files"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_materials",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload reference materials, transcripts, etc."
     *                 ),
     *                 @OA\Property(
     *                     property="test_data",
     *                     type="string",
     *                     description="JSON string containing passages, audio segments and questions data",
     *                     example="{""passages"":[],""audio_segments"":[],""questions"":[]}"
     *                 ),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="settings", type="string", description="JSON object of test settings", example="{""instructions"":""Listen carefully"",""audio_format"":""mp3"",""audio_speed"":1.0}")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Listening test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening test created successfully"),
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
     *     path="/api/v1/listening/tests/{id}",
     *     tags={"Listening Tests"},
     *     summary="Get a specific listening test",
     *     description="Retrieve detailed information about a specific listening test including audio segments and questions.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening test details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="creator_id", type="string", format="uuid"),
     *             @OA\Property(property="creator_name", type="string"),
     *             @OA\Property(property="type", type="string", example="listening"),
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
     *             @OA\Property(property="settings", type="object"),
     *             @OA\Property(property="audio_segments", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="audio_url", type="string"),
     *                     @OA\Property(property="transcript", type="string"),
     *                     @OA\Property(property="duration", type="integer"),
     *                     @OA\Property(property="segment_type", type="string"),
     *                     @OA\Property(property="difficulty_level", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="questions", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="audio_segment_id", type="string", format="uuid"),
     *                     @OA\Property(property="question_text", type="string"),
     *                     @OA\Property(property="question_type", type="string"),
     *                     @OA\Property(property="time_range", type="object"),
     *                     @OA\Property(property="options", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="correct_answer", type="string"),
     *                     @OA\Property(property="explanation", type="string"),
     *                     @OA\Property(property="points", type="integer")
     *                 )
     *             )
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
     * @OA\Post(
     *     path="/api/v1/listening/tests/{id}/update",
     *     tags={"Listening Tests"},
     *     summary="Update a listening test",
     *     description="Update an existing listening test with file upload support",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         required=true,
     *         description="HTTP method override for file uploads",
     *         @OA\Schema(type="string", example="PUT")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Updated IELTS Listening Test"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="test_type", type="string", enum={"academic", "general", "business", "ielts", "toefl"}),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                 @OA\Property(property="timer_mode", type="string", enum={"none", "test", "practice"}),
     *                 @OA\Property(property="timer_settings", type="string", example="{""test_time"":60,""warning_time"":5}"),
     *                 @OA\Property(property="allow_repetition", type="boolean"),
     *                 @OA\Property(property="max_repetition_count", type="integer"),
     *                 @OA\Property(property="is_public", type="boolean"),
     *                 @OA\Property(property="is_published", type="boolean"),
     *                 @OA\Property(property="settings", type="string", example="{""instructions"":""Listen carefully""}"),
     *                 @OA\Property(
     *                     property="audio_files",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload updated test audio files"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_materials", 
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload updated reference materials"
     *                 ),
     *                 @OA\Property(
     *                     property="test_data",
     *                     type="string",
     *                     description="JSON string containing updated test structure",
     *                     example="{""audio_segments"":[],""questions"":[]}"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening test updated successfully"),
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
     *     path="/api/v1/listening/tests/{id}",
     *     tags={"Listening Tests"},
     *     summary="Delete a listening test",
     *     description="Delete a listening test and all related data. Only test creator or admin can delete.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listening test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Listening test deleted successfully")
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
     *     path="/api/v1/listening/tests/{id}/toggle-publish",
     *     tags={"Listening Tests"},
     *     summary="Toggle publish status of a listening test",
     *     description="Toggle the published status of a listening test. Only test creator or admin can perform this action.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Listening test ID",
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
     *     path="/api/v1/listening/tests/search",
     *     tags={"Listening Tests"},
     *     summary="Search listening tests",
     *     description="Search listening tests based on various criteria.",
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