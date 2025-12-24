<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingTaskDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/reading-tasks",
     *     tags={"Reading Tasks"},
     *     summary="Get list of reading tasks",
     *     description="Retrieve reading tasks based on user role - Admin sees all, Teacher sees own tasks, Student sees assigned published tasks",
     *     security={{"bearerAuth":{}}},
     *   
     *     @OA\Response(
     *         response=200,
     *         description="Reading tasks retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading tasks retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="instructions", type="string"),
     *                     @OA\Property(property="is_published", type="boolean"),
     *                     @OA\Property(property="difficulty_level", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "no_timer"}),
     *                     @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                     @OA\Property(property="passage_text", type="string"),
     *                     @OA\Property(property="total_questions", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-tests",
     *     tags={"Reading Tests"},
     *     summary="Create a new reading task",
     *     description="Create a new reading task with passage and questions (15 Question Types supported)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "type", "difficulty", "test_type", "passages"},
     *                 @OA\Property(property="title", type="string", example="IELTS Reading Practice"),
     *                 @OA\Property(property="description", type="string", example="Practice test for IELTS reading section"),
     *                 @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}, example="reading"),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *                 @OA\Property(property="test_type", type="string", enum={"single", "final"}, example="single"),
     *                 @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}, example="countdown"),
     *                 @OA\Property(property="timer_settings", type="string", example="{""hours"":1,""minutes"":0,""seconds"":0}"),
     *                 @OA\Property(property="allow_repetition", type="boolean", example=true),
     *                 @OA\Property(property="max_repetition_count", type="integer", example=3),
     *                 @OA\Property(property="is_public", type="boolean", example=false),
     *                 @OA\Property(property="is_published", type="boolean", example=false),
     *                 @OA\Property(property="settings", type="string", example="{""instructions"":""Read carefully""}"),
     *                 @OA\Property(
     *                     property="passage_images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload images related to the reading passage"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_materials",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload additional reference materials"
     *                 ),
     *                 @OA\Property(
     *                     property="passages",
     *                     type="string",
     *                     description="JSON string of passages array with nested question groups and questions. Supports 15 Reading Question Types: QT1-Multiple Choice, QT2-Multiple Answer, QT3-True/False/Not Given, QT4-Matching Heading, QT5-Sentence Completion, QT6-Paragraph/Summary Completion, QT7-Yes/No/Not Given, QT8-Matching Information, QT9-Matching Features, QT10-Matching Sentence Ending, QT11-Note Completion, QT12-Table Completion, QT13-Flowchart Completion, QT14-Diagram Label Completion, QT15-Short Answer Question",
     *                     example="[{""title"":""Reading Passage 1"",""description"":""Main passage content"",""question_groups"":[{""instruction"":""Answer questions 1-5"",""questions"":[{""question_type"":""QT1"",""question_number"":1,""question_text"":""What is the main topic?"",""options"":[{""option_key"":""A"",""option_text"":""Option A""},{""option_key"":""B"",""option_text"":""Option B""}],""correct_answers"":[""A""],""breakdown"":{""explanation"":""The answer is A because...""}}]}]}]"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/reading-tests/{id}",
     *     tags={"Reading Tests"},
     *     summary="Get reading test details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading test retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="passage_text", type="string"),
     *                 @OA\Property(property="timer_type", type="string"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="question_type", type="string"),
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="options", type="array", @OA\Items(type="string"), nullable=true),
     *                         @OA\Property(property="order", type="integer"),
     *                         @OA\Property(property="explanation", type="string", nullable=true)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-tests/{id}/update",
     *     tags={"Reading Tests"},
     *     summary="Update reading test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", enum={"PUT"}, example="PUT"),
     *                 @OA\Property(property="title", type="string", example="Updated IELTS Reading Practice"),
     *                 @OA\Property(property="description", type="string", example="Updated practice test for IELTS reading section"),
     *                 @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}, example="reading"),
     *                 @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}, example="intermediate"),
     *                 @OA\Property(property="test_type", type="string", enum={"single", "final"}, example="single"),
     *                 @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}, example="countdown"),
     *                 @OA\Property(property="timer_settings", type="string", example="{""hours"":1,""minutes"":30,""seconds"":0}"),
     *                 @OA\Property(property="allow_repetition", type="boolean", example=true),
     *                 @OA\Property(property="max_repetition_count", type="integer", example=3),
     *                 @OA\Property(property="is_public", type="boolean", example=false),
     *                 @OA\Property(property="is_published", type="boolean", example=false),
     *                 @OA\Property(property="settings", type="string", example="{""instructions"":""Read carefully and answer all questions""}"),
     *                 @OA\Property(
     *                     property="passages",
     *                     type="string",
     *                     description="JSON string of passages array with nested question groups and questions",
     *                     example="[{""id"":""existing-uuid"",""title"":""Updated Reading Passage"",""description"":""Updated passage content"",""question_groups"":[{""id"":""group-uuid"",""instruction"":""Answer questions 1-3"",""questions"":[{""id"":""question-uuid"",""question_type"":""QT1"",""question_number"":1,""question_text"":""Updated question text?"",""options"":[{""option_key"":""A"",""option_text"":""Updated Option A""},{""option_key"":""B"",""option_text"":""Updated Option B""}],""correct_answers"":[""A""],""breakdown"":{""explanation"":""Updated explanation...""}}]}]}]"
     *                 ),
     *                 @OA\Property(
     *                     property="passage_images",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload updated images for the reading passage"
     *                 ),
     *                 @OA\Property(
     *                     property="reference_materials",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Upload updated reference materials"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/reading-tests/{id}",
     *     tags={"Reading Tests"},
     *     summary="Delete reading test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading test deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found")
     * )
     */
    public function destroy() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-tasks/{id}/publish",
     *     tags={"Reading Tasks"},
     *     summary="Publish reading task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading task published successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task published successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function publish() {}
}