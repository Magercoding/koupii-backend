<?php

namespace App\Swagger\V1\Listening;

use OpenApi\Annotations as OA;

class ListeningSubmissionDocs
{
    /**
     * @OA\Post(
     *     path="/api/v1/listening/tests/{test}/start",
     *     summary="Start a new listening test",
     *     tags={"Listening Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="test",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="timezone", type="string", example="America/New_York"),
     *             @OA\Property(property="user_agent", type="string", example="Mozilla/5.0..."),
     *             @OA\Property(property="device_info", type="object",
     *                 @OA\Property(property="platform", type="string", example="Windows"),
     *                 @OA\Property(property="browser", type="string", example="Chrome"),
     *                 @OA\Property(property="screen_resolution", type="string", example="1920x1080")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Listening test started"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningSubmissionResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=400, description="Test not available or attempt limit reached")
     * )
     */
    public function startTest() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/tests/{test}",
     *     summary="Get listening test details",
     *     tags={"Listening Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="test",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test details retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningTestDetailsResource")
     *         )
     *     )
     * )
     */
    public function showTest() {}

    /**
     * @OA\Post(
     *     path="/api/v1/listening/submissions/{submission}/submit",
     *     summary="Submit listening test",
     *     tags={"Listening Tests"},
     *     security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="force_submit", type="boolean", example=false),
     *             @OA\Property(property="time_spent", type="integer", example=1800),
     *             @OA\Property(property="completion_notes", type="string", example="Test completed"),
     *             @OA\Property(property="final_review", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Test submitted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningResultResource")
     *         )
     *     )
     * )
     */
    public function submitTest() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions/{submission}",
     *     summary="Get listening submission details",
     *     tags={"Listening Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="submission",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission details retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/ListeningSubmissionResource")
     *         )
     *     )
     * )
     */
    public function getSubmission() {}

    /**
     * @OA\Get(
     *     path="/api/v1/listening/submissions",
     *     summary="Get user's listening submissions",
     *     tags={"Listening Tests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="test_id",
     *         in="query",
     *         description="Filter by test ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"in_progress", "completed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submissions retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ListeningSubmissionResource"))
     *         )
     *     )
     * )
     */
    public function indexSubmissions() {}

    /**
     * @OA\Schema(
     *     schema="ListeningSubmissionResource",
     *     type="object",
     *     title="Listening Submission Resource",
     *     description="Listening test submission data",
     *     @OA\Property(property="id", type="string", format="uuid", description="Submission ID"),
     *     @OA\Property(property="test_id", type="string", format="uuid", description="Test ID"),
     *     @OA\Property(property="student_id", type="string", format="uuid", description="Student ID"),
     *     @OA\Property(property="attempt_number", type="integer", description="Attempt number"),
     *     @OA\Property(property="status", type="string", enum={"in_progress", "completed"}, description="Submission status"),
     *     @OA\Property(property="score", type="number", format="float", description="Total score"),
     *     @OA\Property(property="percentage", type="number", format="float", description="Score percentage"),
     *     @OA\Property(property="total_questions", type="integer", description="Total number of questions"),
     *     @OA\Property(property="answered_questions", type="integer", description="Number of answered questions"),
     *     @OA\Property(property="correct_answers", type="integer", description="Number of correct answers"),
     *     @OA\Property(property="time_spent_minutes", type="integer", description="Time spent in minutes"),
     *     @OA\Property(property="audio_play_counts", type="object", description="Audio play counts by segment"),
     *     @OA\Property(property="started_at", type="string", format="date-time", description="Start timestamp"),
     *     @OA\Property(property="submitted_at", type="string", format="date-time", description="Submission timestamp"),
     *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp"),
     *     @OA\Property(
     *         property="test",
     *         type="object",
     *         description="Test information",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="duration_minutes", type="integer"),
     *         @OA\Property(property="total_questions", type="integer"),
     *         @OA\Property(property="total_points", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="progress",
     *         type="object",
     *         description="Progress indicators",
     *         @OA\Property(property="completion_percentage", type="number", format="float"),
     *         @OA\Property(property="questions_answered", type="integer"),
     *         @OA\Property(property="total_questions", type="integer"),
     *         @OA\Property(property="time_remaining_minutes", type="integer"),
     *         @OA\Property(property="audio_segments_played", type="integer"),
     *         @OA\Property(property="total_audio_plays", type="integer")
     *     ),
     *     @OA\Property(
     *         property="performance",
     *         type="object",
     *         description="Performance metrics (completed submissions only)",
     *         @OA\Property(property="accuracy_percentage", type="number", format="float"),
     *         @OA\Property(property="average_time_per_question", type="number", format="float"),
     *         @OA\Property(property="listening_efficiency", type="number", format="float"),
     *         @OA\Property(property="strengths", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="areas_for_improvement", type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Property(
     *         property="metadata",
     *         type="object",
     *         description="Submission metadata",
     *         @OA\Property(property="can_submit", type="boolean"),
     *         @OA\Property(property="can_edit", type="boolean"),
     *         @OA\Property(property="is_expired", type="boolean"),
     *         @OA\Property(property="submission_deadline", type="string", format="date-time"),
     *         @OA\Property(property="total_audio_duration", type="number", format="float")
     *     )
     * )
     */
    public function submissionSchema() {}

    /**
     * @OA\Schema(
     *     schema="ListeningTestDetailsResource",
     *     type="object",
     *     title="Listening Test Details Resource",
     *     description="Detailed listening test information",
     *     @OA\Property(property="id", type="string", format="uuid", description="Test ID"),
     *     @OA\Property(property="title", type="string", description="Test title"),
     *     @OA\Property(property="description", type="string", description="Test description"),
     *     @OA\Property(property="instructions", type="string", description="Test instructions"),
     *     @OA\Property(property="duration_minutes", type="integer", description="Test duration in minutes"),
     *     @OA\Property(property="total_questions", type="integer", description="Total number of questions"),
     *     @OA\Property(property="total_points", type="number", format="float", description="Total possible points"),
     *     @OA\Property(property="difficulty_level", type="string", description="Test difficulty level"),
     *     @OA\Property(property="test_type", type="string", description="Test type"),
     *     @OA\Property(property="allow_repetition", type="boolean", description="Whether test can be retaken"),
     *     @OA\Property(property="max_repetition_count", type="integer", description="Maximum retake attempts"),
     *     @OA\Property(property="is_published", type="boolean", description="Whether test is published"),
     *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp"),
     *     @OA\Property(
     *         property="passages",
     *         type="array",
     *         description="Test passages with audio",
     *         @OA\Items(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="passage_type", type="string"),
     *             @OA\Property(property="difficulty_level", type="string"),
     *             @OA\Property(property="word_count", type="integer"),
     *             @OA\Property(property="reading_time_minutes", type="integer"),
     *             @OA\Property(property="order", type="integer"),
     *             @OA\Property(
     *                 property="audio_segments",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="audio_url", type="string"),
     *                     @OA\Property(property="start_time", type="number", format="float"),
     *                     @OA\Property(property="end_time", type="number", format="float"),
     *                     @OA\Property(property="duration", type="number", format="float"),
     *                     @OA\Property(property="transcript", type="string"),
     *                     @OA\Property(property="order", type="integer"),
     *                     @OA\Property(property="has_transcript", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Property(
     *         property="questions",
     *         type="array",
     *         description="Test questions",
     *         @OA\Items(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="question_text", type="string"),
     *             @OA\Property(property="question_type", type="string", enum={"multiple_choice", "multiple_select", "true_false", "fill_blank", "gap_fill_dropdown", "match_headings", "summary_completion", "note_completion", "table_completion", "sentence_completion"}),
     *             @OA\Property(property="question_order", type="integer"),
     *             @OA\Property(property="points", type="number", format="float"),
     *             @OA\Property(property="passage_id", type="string", format="uuid"),
     *             @OA\Property(property="question_data", type="object"),
     *             @OA\Property(
     *                 property="options",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="option_text", type="string"),
     *                     @OA\Property(property="order", type="integer"),
     *                     @OA\Property(property="option_data", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Property(
     *         property="metadata",
     *         type="object",
     *         description="Test metadata",
     *         @OA\Property(property="estimated_completion_time", type="integer"),
     *         @OA\Property(property="question_types_summary", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="audio_duration_total", type="number", format="float"),
     *         @OA\Property(property="difficulty_distribution", type="object"),
     *         @OA\Property(property="skill_areas_covered", type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Property(
     *         property="configuration",
     *         type="object",
     *         description="Test configuration",
     *         @OA\Property(property="can_pause", type="boolean"),
     *         @OA\Property(property="can_replay_audio", type="boolean"),
     *         @OA\Property(property="max_audio_replays", type="integer"),
     *         @OA\Property(property="show_transcript", type="boolean"),
     *         @OA\Property(property="show_timer", type="boolean"),
     *         @OA\Property(property="auto_submit", type="boolean"),
     *         @OA\Property(property="randomize_questions", type="boolean"),
     *         @OA\Property(property="randomize_options", type="boolean")
     *     )
     * )
     */
    public function testDetailsSchema() {}

    /**
     * @OA\Schema(
     *     schema="ListeningResultResource",
     *     type="object",
     *     title="Listening Result Resource",
     *     description="Detailed listening test result with analytics",
     *     @OA\Property(property="id", type="string", format="uuid", description="Submission ID"),
     *     @OA\Property(property="test_id", type="string", format="uuid", description="Test ID"),
     *     @OA\Property(property="student_id", type="string", format="uuid", description="Student ID"),
     *     @OA\Property(property="attempt_number", type="integer", description="Attempt number"),
     *     @OA\Property(property="status", type="string", description="Submission status"),
     *     @OA\Property(property="score", type="number", format="float", description="Total score"),
     *     @OA\Property(property="percentage", type="number", format="float", description="Score percentage"),
     *     @OA\Property(property="grade", type="string", description="Letter grade"),
     *     @OA\Property(property="total_questions", type="integer", description="Total questions"),
     *     @OA\Property(property="correct_answers", type="integer", description="Correct answers count"),
     *     @OA\Property(property="incorrect_answers", type="integer", description="Incorrect answers count"),
     *     @OA\Property(property="unanswered_questions", type="integer", description="Unanswered questions count"),
     *     @OA\Property(property="time_spent_minutes", type="integer", description="Time spent in minutes"),
     *     @OA\Property(property="started_at", type="string", format="date-time", description="Start timestamp"),
     *     @OA\Property(property="submitted_at", type="string", format="date-time", description="Submission timestamp"),
     *     @OA\Property(
     *         property="test",
     *         type="object",
     *         description="Test information",
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="duration_minutes", type="integer"),
     *         @OA\Property(property="total_points", type="number", format="float"),
     *         @OA\Property(property="passing_score", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="performance",
     *         type="object",
     *         description="Performance metrics",
     *         @OA\Property(property="accuracy_percentage", type="number", format="float"),
     *         @OA\Property(property="time_efficiency", type="number", format="float"),
     *         @OA\Property(property="listening_efficiency", type="number", format="float"),
     *         @OA\Property(property="comprehension_score", type="number", format="float"),
     *         @OA\Property(property="response_consistency", type="number", format="float")
     *     ),
     *     @OA\Property(
     *         property="audio_engagement",
     *         type="object",
     *         description="Audio engagement metrics",
     *         @OA\Property(property="total_audio_plays", type="integer"),
     *         @OA\Property(property="unique_segments_played", type="integer"),
     *         @OA\Property(property="average_plays_per_segment", type="number", format="float"),
     *         @OA\Property(property="most_replayed_segments", type="object"),
     *         @OA\Property(property="listening_patterns", type="object")
     *     ),
     *     @OA\Property(
     *         property="question_analysis",
     *         type="array",
     *         description="Question type breakdown",
     *         @OA\Items(
     *             @OA\Property(property="question_type", type="string"),
     *             @OA\Property(property="total_questions", type="integer"),
     *             @OA\Property(property="correct_answers", type="integer"),
     *             @OA\Property(property="accuracy_percentage", type="number", format="float"),
     *             @OA\Property(property="average_time", type="number", format="float"),
     *             @OA\Property(property="average_plays", type="number", format="float")
     *         )
     *     ),
     *     @OA\Property(
     *         property="recommendations",
     *         type="object",
     *         description="Personalized recommendations",
     *         @OA\Property(property="strengths", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="areas_for_improvement", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="study_suggestions", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="next_steps", type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Property(
     *         property="benchmarks",
     *         type="object",
     *         description="Comparative metrics",
     *         @OA\Property(property="class_average", type="number", format="float"),
     *         @OA\Property(property="percentile_ranking", type="number", format="float"),
     *         @OA\Property(property="improvement_from_last_attempt", type="object")
     *     )
     * )
     */
    public function resultSchema() {}
}

