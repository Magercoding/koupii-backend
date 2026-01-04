<?php

namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassTestDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/classes/{classId}/tests",
     *     operationId="getClassTests",
     *     tags={"Classes", "Tests"},
     *     summary="📝 Get All Tests for a Class",
     *     description="Retrieve all tests created for a specific class. Only class teachers can access this.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by test type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"reading", "writing", "listening", "speaking", "mixed"})
     *     ),
     *     @OA\Parameter(
     *         name="is_published",
     *         in="query",
     *         description="Filter by publication status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of tests per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Class tests retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class tests retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="IELTS Reading Practice Test 1"),
     *                     @OA\Property(property="description", type="string", example="Comprehensive reading skills assessment"),
     *                     @OA\Property(property="type", type="string", example="reading"),
     *                     @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="statistics",
     *                         type="object",
     *                         @OA\Property(property="total_passages", type="integer", example=3),
     *                         @OA\Property(property="total_questions", type="integer", example=40)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation Advanced"),
     *                 @OA\Property(property="class_code", type="string", example="ABC123"),
     *                 @OA\Property(property="student_count", type="integer", example=18)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Unauthorized - not class teacher",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Class not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to retrieve tests"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getClassTests() {}

    /**
     * @OA\Post(
     *     path="/api/v1/classes/{classId}/tests",
     *     operationId="createClassTest",
     *     tags={"Classes", "Tests"},
     *     summary="🆕 Create New Test for Class",
     *     description="Create a new test specifically for this class. Only the class teacher can create tests.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"title", "description", "type", "difficulty", "timer_mode"},
     *             @OA\Property(property="title", type="string", example="IELTS Reading Practice Test 1", maxLength=255),
     *             @OA\Property(property="description", type="string", example="Practice test focusing on academic reading skills"),
     *             @OA\Property(property="type", type="string", enum={"reading", "writing", "listening", "speaking"}, example="reading"),
     *             @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *             @OA\Property(property="test_type", type="string", enum={"single", "final"}, example="single"),
     *             @OA\Property(property="timer_mode", type="string", enum={"none", "countdown", "countup"}, example="countdown"),
     *             @OA\Property(
     *                 property="timer_settings",
     *                 type="object",
     *                 @OA\Property(property="time_limit", type="integer", example=3600, description="Time limit in seconds"),
     *                 @OA\Property(property="warning_time", type="integer", example=300, description="Warning time in seconds")
     *             ),
     *             @OA\Property(property="allow_repetition", type="boolean", example=false),
     *             @OA\Property(property="max_repetition_count", type="integer", example=2, minimum=1, maximum=10),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="is_published", type="boolean", example=false),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 @OA\Property(property="shuffle_questions", type="boolean", example=false),
     *                 @OA\Property(property="shuffle_options", type="boolean", example=false),
     *                 @OA\Property(property="show_results", type="boolean", example=true)
     *             ),
     *             @OA\Property(
     *                 property="passages",
     *                 type="array",
     *                 description="Array of passages with questions for the test",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="title", type="string", example="Climate Change Passage"),
     *                     @OA\Property(property="description", type="string", example="Academic text about environmental issues"),
     *                     @OA\Property(property="audio_file_path", type="string", example="audio/climate-change.mp3"),
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
     *                                     @OA\Property(property="question_text", type="string", example="What is the main idea of the passage?"),
     *                                     @OA\Property(property="points_value", type="number", example=1),
     *                                     @OA\Property(
     *                                         property="options",
     *                                         type="array",
     *                                         @OA\Items(
     *                                             type="object",
     *                                             @OA\Property(property="option_key", type="string", example="A"),
     *                                             @OA\Property(property="option_text", type="string", example="Climate change is a serious issue")
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
     *         description="✅ Test created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test created successfully for class: IELTS Preparation Advanced"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string", example="IELTS Reading Practice Test 1"),
     *                 @OA\Property(property="type", type="string", example="reading"),
     *                 @OA\Property(property="is_published", type="boolean", example=false)
     *             ),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation Advanced"),
     *                 @OA\Property(property="class_code", type="string", example="ABC123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Validation failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to create test"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function createClassTest() {}

    /**
     * @OA\Get(
     *     path="/api/v1/classes/{classId}/tests/{testId}",
     *     operationId="getClassTest",
     *     tags={"Classes", "Tests"},
     *     summary="📊 Get Class Test Details",
     *     description="Get detailed information about a specific test within a class including statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Test details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test details retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string", example="IELTS Reading Practice Test 1"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string", example="reading"),
     *                 @OA\Property(property="difficulty", type="string", example="intermediate"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="passages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(
     *                     property="class",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="IELTS Preparation Advanced"),
     *                     @OA\Property(property="class_code", type="string", example="ABC123")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="statistics",
     *                 type="object",
     *                 @OA\Property(property="assigned_students", type="integer", example=15),
     *                 @OA\Property(property="completed", type="integer", example=10),
     *                 @OA\Property(property="in_progress", type="integer", example=3),
     *                 @OA\Property(property="not_started", type="integer", example=2)
     *             ),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation Advanced"),
     *                 @OA\Property(property="class_code", type="string", example="ABC123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Test not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test not found"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getClassTest() {}

    /**
     * @OA\Post(
     *     path="/api/v1/classes/{classId}/tests/{testId}/assign",
     *     operationId="assignTestToClass",
     *     tags={"Classes", "Tests"},
     *     summary="📋 Assign Test to All Students in Class",
     *     description="Assign a test to all enrolled students in the class with due date and instructions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"due_date"},
     *             @OA\Property(property="due_date", type="string", format="date-time", example="2026-01-15T23:59:59Z", description="Assignment due date"),
     *             @OA\Property(property="close_date", type="string", format="date-time", example="2026-01-16T23:59:59Z", description="Assignment close date (optional)"),
     *             @OA\Property(property="title", type="string", example="Weekly IELTS Assessment", description="Custom assignment title"),
     *             @OA\Property(property="description", type="string", example="Complete all reading passages and submit answers before due date", description="Assignment instructions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Test assigned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test assigned to all students in IELTS Preparation Advanced"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="test_title", type="string", example="IELTS Reading Practice Test 1"),
     *                 @OA\Property(property="due_date", type="string", format="date-time"),
     *                 @OA\Property(property="assigned_to_students", type="integer", example=18)
     *             ),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation Advanced")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Test must be published or validation failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to assign test"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function assignTestToClass() {}

    /**
     * @OA\Put(
     *     path="/api/v1/classes/{classId}/tests/{testId}",
     *     operationId="updateClassTest",
     *     tags={"Classes", "Tests"},
     *     summary="📝 Update Class Test",
     *     description="Update an existing test within a class",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string", example="Updated Test Title"),
     *             @OA\Property(property="description", type="string", example="Updated test description"),
     *             @OA\Property(property="is_published", type="boolean", example=true),
     *             @OA\Property(property="timer_mode", type="string", enum={"none", "countdown", "countup"}),
     *             @OA\Property(
     *                 property="timer_settings",
     *                 type="object",
     *                 @OA\Property(property="time_limit", type="integer"),
     *                 @OA\Property(property="warning_time", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Test updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="is_published", type="boolean")
     *             )
     *         )
     *     )
     * )
     */
    public function updateClassTest() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/classes/{classId}/tests/{testId}",
     *     operationId="deleteClassTest",
     *     tags={"Classes", "Tests"},
     *     summary="🗑️ Delete Class Test",
     *     description="Delete a test from the class. This will also remove all related assignments and student submissions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Test deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Test deleted successfully from IELTS Preparation Advanced")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Test not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to delete test"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteClassTest() {}
}