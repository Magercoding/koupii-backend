<?php

namespace App\Swagger\V1\Test;

class TestSubmissionDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/assignments/{id}/submit",
     *     tags={"Test Submissions"},
     *     summary="Submit assignment answers",
     *     description="Submit student answers for a specific assignment and calculate scores",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 description="Array of student answers",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"question_id", "answer"},
     *                     @OA\Property(property="question_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(
     *                         property="answer",
     *                         description="Student's answer (can be string, array, or object depending on question type)",
     *                         oneOf={
     *                             @OA\Schema(type="string", example="greenhouse_gases"),
     *                             @OA\Schema(type="array", @OA\Items(type="string")),
     *                             @OA\Schema(type="object")
     *                         }
     *                     ),
     *                     @OA\Property(property="time_taken", type="integer", description="Time spent on question in seconds", example=45, nullable=true)
     *                 )
     *             ),
     *             @OA\Property(property="total_time_taken", type="integer", description="Total time spent on assignment in seconds", example=1800, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assignment submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assignment submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_assignment_id", type="string", format="uuid"),
     *                 @OA\Property(property="result_id", type="string", format="uuid"),
     *                 @OA\Property(property="test_id", type="string", format="uuid"),
     *                 @OA\Property(property="student_id", type="string", format="uuid"),
     *                 @OA\Property(property="total_score", type="number", example=75.5),
     *                 @OA\Property(property="max_score", type="number", example=100),
     *                 @OA\Property(property="percentage", type="number", example=75.5),
     *                 @OA\Property(property="total_questions", type="integer", example=40),
     *                 @OA\Property(property="correct_answers", type="integer", example=30),
     *                 @OA\Property(property="incorrect_answers", type="integer", example=10),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="No test associated with assignment"),
     *     @OA\Response(response=403, description="Not assigned to this assignment or already completed"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function submitAssignment() {}

    /**
     * @OA\Get(
     *     path="/api/v1/assignments/{id}/results",
     *     tags={"Test Submissions"},
     *     summary="Get assignment results",
     *     description="Retrieve student's results for a specific assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assignment ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="assignment",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date-time", nullable=true)
     *                 ),
     *                 @OA\Property(
     *                     property="submissions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="assignment_id", type="string", format="uuid"),
     *                         @OA\Property(property="student_assignment_id", type="string", format="uuid"),
     *                         @OA\Property(property="result_id", type="string", format="uuid"),
     *                         @OA\Property(property="total_score", type="number"),
     *                         @OA\Property(property="percentage", type="number"),
     *                         @OA\Property(property="total_correct", type="integer"),
     *                         @OA\Property(property="total_incorrect", type="integer"),
     *                         @OA\Property(property="total_unanswered", type="integer"),
     *                         @OA\Property(property="completed_at", type="string", format="date-time"),
     *                         @OA\Property(
     *                             property="answers",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="question_id", type="string", format="uuid"),
     *                                 @OA\Property(property="question_text", type="string"),
     *                                 @OA\Property(property="question_type", type="string"),
     *                                 @OA\Property(property="student_answer", type="string"),
     *                                 @OA\Property(property="correct_answer", type="object"),
     *                                 @OA\Property(property="is_correct", type="boolean"),
     *                                 @OA\Property(property="points_earned", type="number"),
     *                                 @OA\Property(property="max_points", type="number"),
     *                                 @OA\Property(property="time_spent", type="integer", nullable=true)
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No completed submissions found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function assignmentResults() {}

    /**
     * @OA\Get(
     *     path="/api/v1/tests/{id}/attempt",
     *     tags={"Test Submissions"},
     *     summary="Start test attempt",
     *     description="Get test content for student to attempt (for practice tests or public tests)",
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
     *         description="Test attempt data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="test",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="type", type="string", enum={"reading", "listening", "speaking", "writing"}),
     *                     @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="timer_mode", type="string", enum={"countdown", "countup", "none"}),
     *                     @OA\Property(property="timer_settings", type="object"),
     *                     @OA\Property(property="allow_repetition", type="boolean"),
     *                     @OA\Property(property="max_repetition_count", type="integer", nullable=true)
     *                 ),
     *                 @OA\Property(
     *                     property="passages",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="description", type="string"),
     *                         @OA\Property(property="audio_file_path", type="string", nullable=true),
     *                         @OA\Property(property="transcript", type="object", nullable=true),
     *                         @OA\Property(
     *                             property="question_groups",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="string", format="uuid"),
     *                                 @OA\Property(property="instruction", type="string"),
     *                                 @OA\Property(
     *                                     property="questions",
     *                                     type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="string", format="uuid"),
     *                                         @OA\Property(property="question_type", type="string"),
     *                                         @OA\Property(property="question_number", type="number"),
     *                                         @OA\Property(property="question_text", type="string"),
     *                                         @OA\Property(property="question_data", type="object"),
     *                                         @OA\Property(property="points_value", type="number"),
     *                                         @OA\Property(
     *                                             property="options",
     *                                             type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="string", format="uuid"),
     *                                                 @OA\Property(property="option_key", type="string"),
     *                                                 @OA\Property(property="option_text", type="string")
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="attempt_info",
     *                     type="object",
     *                     @OA\Property(property="can_attempt", type="boolean"),
     *                     @OA\Property(property="attempts_used", type="integer"),
     *                     @OA\Property(property="max_attempts", type="integer", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Test repetition not allowed or max attempts reached"),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function attempt() {}
}