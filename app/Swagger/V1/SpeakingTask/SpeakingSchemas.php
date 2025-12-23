<?php

namespace App\Swagger\V1\SpeakingTask;

use OpenApi\Annotations as OA;

class SpeakingSchemas
{
    /**
     * @OA\Schema(
     *     schema="SpeakingDashboardResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *     @OA\Property(property="test_id", type="string", format="uuid"),
     *     @OA\Property(property="due_date", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="assigned_at", type="string", format="date-time"),
     *     @OA\Property(property="allow_retake", type="boolean"),
     *     @OA\Property(property="max_attempts", type="integer"),
     *     @OA\Property(property="test", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="title", type="string", example="Introduction Speaking Task"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="instructions", type="string"),
     *         @OA\Property(property="difficulty", type="string", enum={"beginner", "intermediate", "advanced"}),
     *         @OA\Property(property="timer_type", type="string", enum={"countdown", "countup", "none"}),
     *         @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *         @OA\Property(property="time_limit_formatted", type="string", nullable=true, example="2:00")
     *     ),
     *     @OA\Property(property="class", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="name", type="string", example="English A1 Class")
     *     ),
     *     @OA\Property(property="assigned_by", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="name", type="string", example="Teacher Name")
     *     ),
     *     @OA\Property(property="submission_status", type="string", enum={"to_do", "in_progress", "submitted", "reviewed"}),
     *     @OA\Property(property="latest_submission", type="object", nullable=true,
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="attempt_number", type="integer"),
     *         @OA\Property(property="started_at", type="string", format="date-time"),
     *         @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *         @OA\Property(property="total_time_seconds", type="integer", nullable=true),
     *         @OA\Property(property="total_time_formatted", type="string", nullable=true),
     *         @OA\Property(property="review", type="object", nullable=true,
     *             @OA\Property(property="overall_score", type="integer", nullable=true),
     *             @OA\Property(property="feedback", type="string", nullable=true),
     *             @OA\Property(property="reviewed_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Property(property="status_info", type="object",
     *         @OA\Property(property="label", type="string"),
     *         @OA\Property(property="color", type="string"),
     *         @OA\Property(property="icon", type="string"),
     *         @OA\Property(property="description", type="string")
     *     ),
     *     @OA\Property(property="is_overdue", type="boolean"),
     *     @OA\Property(property="days_remaining", type="integer", nullable=true)
     * )
     */

    /**
     * @OA\Schema(
     *     schema="SpeakingTaskDetailResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="test_id", type="string", format="uuid"),
     *     @OA\Property(property="due_date", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="assigned_at", type="string", format="date-time"),
     *     @OA\Property(property="allow_retake", type="boolean"),
     *     @OA\Property(property="max_attempts", type="integer"),
     *     @OA\Property(property="test", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="instructions", type="string"),
     *         @OA\Property(property="difficulty", type="string"),
     *         @OA\Property(property="timer_type", type="string"),
     *         @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *         @OA\Property(property="sections", type="array", @OA\Items(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="order_index", type="integer"),
     *             @OA\Property(property="time_limit_seconds", type="integer", nullable=true),
     *             @OA\Property(property="questions", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="topic", type="string"),
     *                 @OA\Property(property="prompt", type="string"),
     *                 @OA\Property(property="preparation_time_seconds", type="integer"),
     *                 @OA\Property(property="response_time_seconds", type="integer"),
     *                 @OA\Property(property="order_index", type="integer")
     *             ))
     *         ))
     *     ),
     *     @OA\Property(property="submissions", type="array", @OA\Items(ref="#/components/schemas/SpeakingSubmissionResource")),
     *     @OA\Property(property="can_start", type="boolean"),
     *     @OA\Property(property="can_retake", type="boolean"),
     *     @OA\Property(property="attempts_remaining", type="integer"),
     *     @OA\Property(property="submission_status", type="string"),
     *     @OA\Property(property="is_overdue", type="boolean")
     * )
     */

    /**
     * @OA\Schema(
     *     schema="SpeakingSubmissionResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="assignment_id", type="string", format="uuid"),
     *     @OA\Property(property="student_id", type="string", format="uuid"),
     *     @OA\Property(property="status", type="string", enum={"in_progress", "submitted", "reviewed"}),
     *     @OA\Property(property="attempt_number", type="integer"),
     *     @OA\Property(property="started_at", type="string", format="date-time"),
     *     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="total_time_seconds", type="integer", nullable=true),
     *     @OA\Property(property="total_time_formatted", type="string", nullable=true),
     *     @OA\Property(property="assignment", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="due_date", type="string", format="date-time", nullable=true),
     *         @OA\Property(property="test", type="object",
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string")
     *         ),
     *         @OA\Property(property="class", type="object",
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Property(property="student", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string")
     *     ),
     *     @OA\Property(property="recordings", type="array", @OA\Items(ref="#/components/schemas/SpeakingRecordingResource")),
     *     @OA\Property(property="review", type="object", nullable=true, ref="#/components/schemas/SpeakingReviewResource"),
     *     @OA\Property(property="speech_analysis_summary", type="object", nullable=true,
     *         @OA\Property(property="total_speaking_time", type="integer"),
     *         @OA\Property(property="average_confidence", type="number", format="float"),
     *         @OA\Property(property="average_fluency", type="number", format="float"),
     *         @OA\Property(property="average_speaking_rate", type="number", format="float"),
     *         @OA\Property(property="total_words", type="integer"),
     *         @OA\Property(property="questions_completed", type="integer"),
     *         @OA\Property(property="has_transcript", type="boolean")
     *     ),
     *     @OA\Property(property="progress", type="object",
     *         @OA\Property(property="completed_recordings", type="integer"),
     *         @OA\Property(property="total_questions", type="integer"),
     *         @OA\Property(property="completion_percentage", type="number", format="float"),
     *         @OA\Property(property="is_complete", type="boolean")
     *     )
     * )
     */

    /**
     * @OA\Schema(
     *     schema="SpeakingRecordingResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="submission_id", type="string", format="uuid"),
     *     @OA\Property(property="question_id", type="string", format="uuid"),
     *     @OA\Property(property="file_name", type="string", example="recording_001.mp3"),
     *     @OA\Property(property="file_path", type="string"),
     *     @OA\Property(property="file_url", type="string", nullable=true),
     *     @OA\Property(property="duration_seconds", type="integer", nullable=true),
     *     @OA\Property(property="duration_formatted", type="string", nullable=true, example="1:45"),
     *     @OA\Property(property="file_size", type="integer", nullable=true),
     *     @OA\Property(property="file_size_formatted", type="string", nullable=true, example="2.5 MB"),
     *     @OA\Property(property="transcript", type="string", nullable=true),
     *     @OA\Property(property="confidence_score", type="number", format="float", nullable=true, minimum=0, maximum=1),
     *     @OA\Property(property="fluency_score", type="number", format="float", nullable=true),
     *     @OA\Property(property="speaking_rate", type="number", format="float", nullable=true),
     *     @OA\Property(property="pause_analysis", type="object", nullable=true),
     *     @OA\Property(property="word_count", type="integer", nullable=true),
     *     @OA\Property(property="speech_processed", type="boolean"),
     *     @OA\Property(property="speech_processed_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="question", type="object", nullable=true,
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="topic", type="string"),
     *         @OA\Property(property="prompt", type="string"),
     *         @OA\Property(property="preparation_time_seconds", type="integer"),
     *         @OA\Property(property="response_time_seconds", type="integer")
     *     ),
     *     @OA\Property(property="quality_indicators", type="object", nullable=true,
     *         @OA\Property(property="confidence_level", type="string", enum={"very_low", "low", "medium", "high", "very_high"}),
     *         @OA\Property(property="fluency_level", type="string", enum={"very_poor", "poor", "fair", "good", "excellent"}),
     *         @OA\Property(property="speaking_pace", type="string", enum={"very_slow", "slow", "normal", "fast", "very_fast"}),
     *         @OA\Property(property="has_long_pauses", type="boolean")
     *     )
     * )
     */

    /**
     * @OA\Schema(
     *     schema="SpeakingReviewResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="submission_id", type="string", format="uuid"),
     *     @OA\Property(property="overall_score", type="integer", minimum=0, maximum=100),
     *     @OA\Property(property="pronunciation_score", type="integer", minimum=0, maximum=100, nullable=true),
     *     @OA\Property(property="fluency_score", type="integer", minimum=0, maximum=100, nullable=true),
     *     @OA\Property(property="grammar_score", type="integer", minimum=0, maximum=100, nullable=true),
     *     @OA\Property(property="vocabulary_score", type="integer", minimum=0, maximum=100, nullable=true),
     *     @OA\Property(property="content_score", type="integer", minimum=0, maximum=100, nullable=true),
     *     @OA\Property(property="feedback", type="string", nullable=true),
     *     @OA\Property(property="detailed_comments", type="string", nullable=true),
     *     @OA\Property(property="strengths", type="string", nullable=true),
     *     @OA\Property(property="areas_for_improvement", type="string", nullable=true),
     *     @OA\Property(property="reviewed_at", type="string", format="date-time"),
     *     @OA\Property(property="reviewed_by", type="object", nullable=true,
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="name", type="string")
     *     ),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */

    /**
     * @OA\Schema(
     *     schema="SpeakingTeacherDashboardResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="assignment_id", type="string", format="uuid"),
     *     @OA\Property(property="attempt_number", type="integer"),
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="started_at", type="string", format="date-time"),
     *     @OA\Property(property="submitted_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="total_time_seconds", type="integer", nullable=true),
     *     @OA\Property(property="total_time_formatted", type="string", nullable=true),
     *     @OA\Property(property="student", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string")
     *     ),
     *     @OA\Property(property="assignment", type="object",
     *         @OA\Property(property="id", type="string", format="uuid"),
     *         @OA\Property(property="due_date", type="string", format="date-time", nullable=true),
     *         @OA\Property(property="test", type="object",
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="difficulty", type="string")
     *         ),
     *         @OA\Property(property="class", type="object",
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Property(property="speech_summary", type="object",
     *         @OA\Property(property="total_recordings", type="integer"),
     *         @OA\Property(property="total_speaking_time", type="integer"),
     *         @OA\Property(property="total_speaking_time_formatted", type="string"),
     *         @OA\Property(property="average_confidence", type="number", format="float"),
     *         @OA\Property(property="average_fluency", type="number", format="float"),
     *         @OA\Property(property="average_speaking_rate", type="number", format="float"),
     *         @OA\Property(property="total_words", type="integer"),
     *         @OA\Property(property="has_transcripts", type="boolean")
     *     ),
     *     @OA\Property(property="review_status", type="object",
     *         @OA\Property(property="is_reviewed", type="boolean"),
     *         @OA\Property(property="needs_review", type="boolean"),
     *         @OA\Property(property="review_score", type="integer", nullable=true),
     *         @OA\Property(property="reviewed_at", type="string", format="date-time", nullable=true),
     *         @OA\Property(property="reviewed_by", type="object", nullable=true,
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Property(property="submission_age_hours", type="number", format="float", nullable=true),
     *     @OA\Property(property="is_overdue", type="boolean"),
     *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high"})
     * )
     */
}