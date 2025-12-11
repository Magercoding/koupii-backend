<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningAnswerDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/listening/submissions/{submission}/answers",
     *     summary="Save answer for a listening question",
     *     tags={"Listening Answers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="selected_option_id", type="string", format="uuid"),
     *             @OA\Property(property="text_answer", type="string"),
     *             @OA\Property(property="answer_data", type="object",
     *                 @OA\Property(property="selected_options", type="array", @OA\Items(type="string", format="uuid")),
     *                 @OA\Property(property="gaps", type="object"),
     *                 @OA\Property(property="matches", type="object"),
     *                 @OA\Property(property="cells", type="object"),
     *                 @OA\Property(property="sequence", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="coordinates", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="audio_timestamps", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="time_spent_seconds", type="integer"),
     *             @OA\Property(property="play_count", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Answer saved"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningQuestionAnswerResource")
     *         )
     *     )
     * )
     */
    public function saveAnswer() {}

    /**
     * @OA\Put(
     *     path="/api/v1/listening/answers/{answer}",
     *     summary="Update a listening answer",
     *     tags={"Listening Answers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="answer",
     *         in="path",
     *         description="Answer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="selected_option_id", type="string", format="uuid"),
     *             @OA\Property(property="text_answer", type="string"),
     *             @OA\Property(property="answer_data", type="object"),
     *             @OA\Property(property="time_spent_seconds", type="integer"),
     *             @OA\Property(property="play_count", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Answer updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningQuestionAnswerResource")
     *         )
     *     )
     * )
     */
    public function updateAnswer() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{submission}/answers",
     *     summary="Get all answers for a listening submission",
     *     tags={"Listening Answers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answers retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ListeningQuestionAnswerResource"))
     *         )
     *     )
     * )
     */
    public function getAnswers() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/answers/{answer}",
     *     summary="Get specific listening answer",
     *     tags={"Listening Answers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="answer",
     *         in="path",
     *         description="Answer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningQuestionAnswerResource")
     *         )
     *     )
     * )
     */
    public function getAnswer() {}

    /**
     * @OA\Schema(
     *     schema="ListeningQuestionAnswerResource",
     *     type="object",
     *     title="Listening Question Answer Resource",
     *     description="Listening question answer data",
     *     @OA\Property(property="id", type="string", format="uuid", description="Answer ID"),
     *     @OA\Property(property="submission_id", type="string", format="uuid", description="Submission ID"),
     *     @OA\Property(property="question_id", type="string", format="uuid", description="Question ID"),
     *     @OA\Property(property="selected_option_id", type="string", format="uuid", description="Selected option ID"),
     *     @OA\Property(property="text_answer", type="string", description="Text answer"),
     *     @OA\Property(property="answer_data", type="object", description="Structured answer data"),
     *     @OA\Property(property="is_correct", type="boolean", description="Whether answer is correct"),
     *     @OA\Property(property="points_earned", type="number", format="float", description="Points earned"),
     *     @OA\Property(property="time_spent_seconds", type="integer", description="Time spent on question"),
     *     @OA\Property(property="play_count", type="integer", description="Number of audio plays"),
     *     @OA\Property(property="answer_explanation", type="string", description="Answer explanation"),
     *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp"),
     *     @OA\Property(property="time_spent_formatted", type="string", description="Formatted time spent (MM:SS)"),
     *     @OA\Property(property="is_answered", type="boolean", description="Whether question is answered"),
     *     @OA\Property(property="confidence_score", type="number", format="float", description="Answer confidence score"),
     *     @OA\Property(property="answer_summary", type="string", description="Answer summary for display"),
     *     @OA\Property(
     *         property="question",
     *         type="object",
     *         description="Question information",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="question_text", type="string"),
     *         @OA\Property(property="question_type", type="string"),
     *         @OA\Property(property="question_order", type="integer"),
     *         @OA\Property(property="points", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="selected_option",
     *         type="object",
     *         description="Selected option details",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="option_text", type="string"),
     *         @OA\Property(property="is_correct", type="boolean"),
     *         @OA\Property(property="explanation", type="string")
     *     ),
     *     @OA\Property(
     *         property="performance",
     *         type="object",
     *         description="Performance insights",
     *         @OA\Property(property="difficulty_assessment", type="string", enum={"Easy", "Moderate", "Challenging", "Very Difficult"}),
     *         @OA\Property(property="listening_pattern", type="string", enum={"Single listen", "Careful listener", "Multiple replays", "Extensive replaying"}),
     *         @OA\Property(property="response_quality", type="string", enum={"Excellent", "Good", "Needs improvement"})
     *     )
     * )
     */
    public function answerSchema() {}
}