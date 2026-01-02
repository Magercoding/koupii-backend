<?php

namespace App\Swagger\V1\ReadingTask;
use OpenApi\Annotations as OA;
class ReadingTaskDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/reading-tasks",
     *     tags={"Reading Tasks"},
     *     summary="Create a new reading task",
     *     description="Create a new reading task with passages and questions (15 Question Types supported)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "type", "difficulty", "test_type", "passages"},
     *                 @OA\Property(property="title", type="string", example="IELTS Reading Practice"),
     *                 @OA\Property(property="description", type="string", example="Practice test for IELTS reading section"),
     *                 @OA\Property(property="instructions", type="string", example="Read the passages and answer all questions"),
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
     *                     property="passages",
     *                     type="string",
     *                     description="JSON string of passages array with nested question groups and questions. Supports 15 Reading Question Types: QT1-Multiple Choice, QT2-Multiple Answer, QT3-True/False/Not Given, QT4-Matching Heading, QT5-Sentence Completion, QT6-Paragraph/Summary Completion, QT7-Yes/No/Not Given, QT8-Matching Information, QT9-Matching Features, QT10-Matching Sentence Ending, QT11-Note Completion, QT12-Table Completion, QT13-Flowchart Completion, QT14-Diagram Label Completion, QT15-Short Answer Question",
     *                     example="[{""title"":""Reading Passage 1"",""description"":""Main passage content"",""question_groups"":[{""instruction"":""Answer questions 1-5"",""questions"":[{""question_type"":""QT1"",""question_number"":1,""question_text"":""What is the main topic?"",""options"":[{""option_key"":""A"",""option_text"":""Option A""},{""option_key"":""B"",""option_text"":""Option B""}],""correct_answers"":[""A""],""breakdown"":{""explanation"":""The answer is A because...""}}]}]}]"
     *                 ),
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
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reading task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/v1/reading-tasks/{id}",
     *     tags={"Reading Tasks"},
     *     summary="Get reading task details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="passages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="timer_type", type="string"),
     *                 @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *                 @OA\Property(property="difficulty", type="string"),
     *                 @OA\Property(property="is_published", type="boolean"),
     *                 @OA\Property(property="total_questions", type="integer"),
     *                 @OA\Property(property="estimated_time", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show() {}

    /**
     * @OA\Post(
     *     path="/api/v1/reading-tasks/{id}/update",
     *     tags={"Reading Tasks"},
     *     summary="Update reading task",
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
     *         description="Reading task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task updated successfully"),
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
     *     path="/api/v1/reading-tasks/{id}",
     *     tags={"Reading Tasks"},
     *     summary="Delete reading task",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reading task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reading task deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy() {}
}