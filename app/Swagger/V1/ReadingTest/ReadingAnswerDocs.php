<?php

namespace App\Swagger\V1\ReadingTest;

use OpenApi\Annotations as OA;

class ReadingAnswerDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/reading/submissions/{submissionId}/answers",
     *     tags={"Reading Answers"},
     *     summary="Submit answer for a question",
     *     description="Submit student's answer for a specific question in the reading test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="question_id", type="string", format="uuid", description="Question ID"),
     *             @OA\Property(
     *                 property="answer", 
     *                 description="Student's answer (format depends on question type)",
     *                 oneOf={
     *                     @OA\Schema(type="string", description="For single choice, text completion, true/false"),
     *                     @OA\Schema(type="array", @OA\Items(type="string"), description="For multiple choice, matching"),
     *                     @OA\Schema(type="object", description="For complex matching questions")
     *                 }
     *             ),
     *             @OA\Property(property="time_spent_seconds", type="integer", description="Time spent on this question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Answer submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="question_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_answer", type="string"),
     *                 @OA\Property(property="is_correct", type="boolean", nullable=true),
     *                 @OA\Property(property="points_earned", type="number", format="float"),
     *                 @OA\Property(property="time_spent_seconds", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Invalid answer format"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Not your submission")
     * )
     */
    public function submitAnswer()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reading/submissions/{submissionId}/submit",
     *     tags={"Reading Answers"},
     *     summary="Submit the entire test",
     *     description="Submit all answers and complete the reading test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="time_taken_seconds", type="integer", description="Total time taken for the test"),
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 description="Any remaining answers to submit",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="string", format="uuid"),
     *                     @OA\Property(property="answer", type="string"),
     *                     @OA\Property(property="time_spent_seconds", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Test submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="total_score", type="number", format="float"),
     *                 @OA\Property(property="percentage", type="number", format="float"),
     *                 @OA\Property(property="grade", type="string"),
     *                 @OA\Property(property="total_correct", type="integer"),
     *                 @OA\Property(property="total_incorrect", type="integer"),
     *                 @OA\Property(property="total_unanswered", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Test already submitted"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Not your submission")
     * )
     */
    public function submitTest()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reading/submissions/{submissionId}/results",
     *     tags={"Reading Answers"},
     *     summary="Get test results with explanations",
     *     description="Retrieve test results including highlights and explanations for completed tests",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="submissionId",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submission_summary",
     *                     type="object",
     *                     @OA\Property(property="total_score", type="number", format="float"),
     *                     @OA\Property(property="percentage", type="number", format="float"),
     *                     @OA\Property(property="grade", type="string"),
     *                     @OA\Property(property="total_correct", type="integer"),
     *                     @OA\Property(property="total_incorrect", type="integer"),
     *                     @OA\Property(property="time_taken", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="question_results",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="question_id", type="string", format="uuid"),
     *                         @OA\Property(property="question_text", type="string"),
     *                         @OA\Property(property="question_type", type="string"),
     *                         @OA\Property(property="student_answer", type="string"),
     *                         @OA\Property(property="correct_answer", type="string"),
     *                         @OA\Property(property="is_correct", type="boolean"),
     *                         @OA\Property(property="points_earned", type="number", format="float"),
     *                         @OA\Property(
     *                             property="highlights",
     *                             type="array",
     *                             description="Text highlights with explanations",
     *                             @OA\Items(
     *                                 @OA\Property(property="text", type="string"),
     *                                 @OA\Property(property="explanation", type="string"),
     *                                 @OA\Property(property="color", type="string"),
     *                                 @OA\Property(property="start_position", type="integer"),
     *                                 @OA\Property(property="end_position", type="integer")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request - Test not completed"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Not your submission")
     * )
     */
    public function getResults()
    {
    }
}

