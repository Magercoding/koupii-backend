<?php

namespace App\Swagger\V1\SpeakingTask;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Koupii Speaking Module API",
 *     description="Comprehensive API documentation for speaking module with advanced speech analysis and evaluation",
 *     @OA\Contact(
 *         email="developer@koupii.com"
 *     )
 * )
 */

/**
 * @OA\Server(
 *     url="/api/v1",
 *     description="Koupii API V1"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Tasks",
 *     description="Manage speaking tasks and exercises"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Questions",
 *     description="Manage speaking questions and prompts"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Submissions",
 *     description="Student submission management and speech analysis"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Recordings",
 *     description="Audio recording upload and processing"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Reviews",
 *     description="Teacher review and grading for speaking submissions"
 * )
 */

/**
 * @OA\Tag(
 *     name="Speaking Analytics",
 *     description="Analytics and reporting for speaking module"
 * )
 */

// ===== SCHEMAS =====

/**
 * @OA\Schema(
 *     schema="SpeakingTask",
 *     type="object",
 *     required={"title", "sections", "difficulty_level"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Task UUID"),
 *     @OA\Property(property="title", type="string", maxLength=255, description="Task title"),
 *     @OA\Property(property="description", type="string", description="Task description"),
 *     @OA\Property(
 *         property="difficulty_level", 
 *         type="string", 
 *         enum={"beginner", "intermediate", "advanced"}, 
 *         description="Task difficulty"
 *     ),
 *     @OA\Property(property="total_duration", type="integer", description="Total task duration in seconds"),
 *     @OA\Property(property="max_score", type="integer", description="Maximum possible score"),
 *     @OA\Property(property="is_published", type="boolean", description="Publication status"),
 *     @OA\Property(
 *         property="timer_type", 
 *         type="string", 
 *         enum={"countdown", "stopwatch", "none"}, 
 *         description="Timer display type"
 *     ),
 *     @OA\Property(property="instructions", type="string", description="General task instructions"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="sections",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SpeakingSection")
 *     ),
 *     @OA\Property(
 *         property="evaluation_criteria",
 *         type="object",
 *         @OA\Property(property="fluency_weight", type="number", minimum=0, maximum=1),
 *         @OA\Property(property="pronunciation_weight", type="number", minimum=0, maximum=1),
 *         @OA\Property(property="vocabulary_weight", type="number", minimum=0, maximum=1),
 *         @OA\Property(property="grammar_weight", type="number", minimum=0, maximum=1)
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingSection",
 *     type="object",
 *     required={"title", "order_index"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Section UUID"),
 *     @OA\Property(property="title", type="string", maxLength=255, description="Section title"),
 *     @OA\Property(property="description", type="string", description="Section description"),
 *     @OA\Property(property="order_index", type="integer", minimum=1, description="Section order"),
 *     @OA\Property(property="time_limit", type="integer", description="Section time limit in seconds"),
 *     @OA\Property(
 *         property="questions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SpeakingQuestion")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingQuestion",
 *     type="object",
 *     required={"question_text", "question_type", "preparation_time", "response_time"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Question UUID"),
 *     @OA\Property(property="question_text", type="string", maxLength=1000, description="Question text"),
 *     @OA\Property(
 *         property="question_type", 
 *         type="string", 
 *         enum={"describe", "narrate", "opinion", "compare", "analyze", "present", "debate"}, 
 *         description="Question type"
 *     ),
 *     @OA\Property(property="instruction", type="string", maxLength=2000, description="Specific instructions"),
 *     @OA\Property(property="preparation_time", type="integer", minimum=0, maximum=300, description="Preparation time in seconds"),
 *     @OA\Property(property="response_time", type="integer", minimum=30, maximum=600, description="Response time in seconds"),
 *     @OA\Property(
 *         property="difficulty_level", 
 *         type="string", 
 *         enum={"beginner", "intermediate", "advanced"}, 
 *         description="Question difficulty"
 *     ),
 *     @OA\Property(property="max_score", type="integer", minimum=1, maximum=100, description="Maximum score"),
 *     @OA\Property(property="order_index", type="integer", minimum=1, description="Question order"),
 *     @OA\Property(
 *         property="keywords",
 *         type="array",
 *         @OA\Items(type="string", maxLength=50),
 *         description="Related keywords"
 *     ),
 *     @OA\Property(property="sample_response", type="string", maxLength=3000, description="Sample response"),
 *     @OA\Property(property="context_information", type="string", maxLength=1500, description="Additional context"),
 *     @OA\Property(
 *         property="evaluation_criteria",
 *         type="object",
 *         @OA\Property(property="fluency", type="string", maxLength=500),
 *         @OA\Property(property="pronunciation", type="string", maxLength=500),
 *         @OA\Property(property="vocabulary", type="string", maxLength=500),
 *         @OA\Property(property="grammar", type="string", maxLength=500)
 *     ),
 *     @OA\Property(
 *         property="visual_aids",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="type", type="string", enum={"image", "chart", "diagram", "video"}),
 *             @OA\Property(property="url", type="string", format="uri"),
 *             @OA\Property(property="description", type="string", maxLength=255)
 *         )
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingSubmission",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Submission UUID"),
 *     @OA\Property(property="assignment_id", type="string", format="uuid", description="Assignment UUID"),
 *     @OA\Property(property="student_id", type="string", format="uuid", description="Student UUID"),
 *     @OA\Property(property="started_at", type="string", format="date-time"),
 *     @OA\Property(property="submitted_at", type="string", format="date-time"),
 *     @OA\Property(property="total_score", type="number", minimum=0, maximum=100),
 *     @OA\Property(
 *         property="status", 
 *         type="string", 
 *         enum={"draft", "in_progress", "submitted", "graded"}, 
 *         description="Submission status"
 *     ),
 *     @OA\Property(property="time_spent", type="integer", description="Time spent in seconds"),
 *     @OA\Property(property="attempt_number", type="integer", minimum=1, maximum=10),
 *     @OA\Property(
 *         property="device_info",
 *         type="object",
 *         @OA\Property(property="browser", type="string", maxLength=255),
 *         @OA\Property(property="os", type="string", maxLength=255),
 *         @OA\Property(property="microphone", type="string", maxLength=255),
 *         @OA\Property(property="audio_quality", type="string", enum={"high", "medium", "low"})
 *     ),
 *     @OA\Property(
 *         property="answers",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/SpeakingAnswer")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingAnswer",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Answer UUID"),
 *     @OA\Property(property="question_id", type="string", format="uuid", description="Question UUID"),
 *     @OA\Property(property="audio_url", type="string", format="uri", description="Recorded audio URL"),
 *     @OA\Property(property="transcript", type="string", maxLength=5000, description="Speech transcript"),
 *     @OA\Property(property="duration_seconds", type="number", minimum=1, maximum=1800, description="Recording duration"),
 *     @OA\Property(property="confidence_score", type="number", minimum=0, maximum=1, description="Speech recognition confidence"),
 *     @OA\Property(property="preparation_time_used", type="integer", minimum=0, maximum=300),
 *     @OA\Property(property="attempt_count", type="integer", minimum=1, maximum=5),
 *     @OA\Property(property="is_final", type="boolean", description="Final submission flag"),
 *     @OA\Property(property="score", type="number", minimum=0, maximum=100, description="Question score"),
 *     @OA\Property(
 *         property="audio_metadata",
 *         type="object",
 *         @OA\Property(property="sample_rate", type="integer", minimum=8000, maximum=48000),
 *         @OA\Property(property="bit_rate", type="integer", minimum=32, maximum=320),
 *         @OA\Property(property="channels", type="integer", enum={1, 2}),
 *         @OA\Property(property="format", type="string", enum={"mp3", "wav", "m4a", "aac", "ogg", "webm"}),
 *         @OA\Property(property="file_size_bytes", type="integer", minimum=1)
 *     ),
 *     @OA\Property(
 *         property="speech_analysis",
 *         type="object",
 *         @OA\Property(property="speaking_rate", type="number", minimum=0, description="Words per minute"),
 *         @OA\Property(property="pause_count", type="integer", minimum=0, description="Number of pauses"),
 *         @OA\Property(property="pause_duration", type="number", minimum=0, description="Total pause duration"),
 *         @OA\Property(property="volume_level", type="string", enum={"very_low", "low", "normal", "high", "very_high"}),
 *         @OA\Property(property="clarity_score", type="number", minimum=0, maximum=1, description="Speech clarity score"),
 *         @OA\Property(property="fluency_score", type="number", minimum=0, maximum=1, description="Speech fluency score")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingReview",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Review UUID"),
 *     @OA\Property(property="submission_id", type="string", format="uuid", description="Submission UUID"),
 *     @OA\Property(property="reviewer_id", type="string", format="uuid", description="Reviewer UUID"),
 *     @OA\Property(property="total_score", type="number", minimum=0, maximum=100, description="Overall score"),
 *     @OA\Property(property="overall_feedback", type="string", maxLength=2000, description="General feedback"),
 *     @OA\Property(
 *         property="grading_rubric",
 *         type="object",
 *         @OA\Property(property="fluency", type="number", minimum=0, maximum=25, description="Fluency score"),
 *         @OA\Property(property="pronunciation", type="number", minimum=0, maximum=25, description="Pronunciation score"),
 *         @OA\Property(property="vocabulary", type="number", minimum=0, maximum=25, description="Vocabulary score"),
 *         @OA\Property(property="grammar", type="number", minimum=0, maximum=25, description="Grammar score")
 *     ),
 *     @OA\Property(
 *         property="review_status", 
 *         type="string", 
 *         enum={"draft", "completed", "needs_revision"}, 
 *         description="Review status"
 *     ),
 *     @OA\Property(property="review_notes", type="string", maxLength=1000, description="Internal review notes"),
 *     @OA\Property(property="time_spent_reviewing", type="integer", minimum=0, description="Review time in seconds"),
 *     @OA\Property(property="reviewed_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="recommendations",
 *         type="object",
 *         @OA\Property(
 *             property="strengths", 
 *             type="array", 
 *             @OA\Items(type="string"), 
 *             description="Student strengths"
 *         ),
 *         @OA\Property(
 *             property="areas_for_improvement", 
 *             type="array", 
 *             @OA\Items(type="string"), 
 *             description="Areas needing improvement"
 *         ),
 *         @OA\Property(property="next_steps", type="string", maxLength=1000, description="Recommended next steps")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingTaskCollection",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/SpeakingTask")
 *         ),
 *         @OA\Property(property="first_page_url", type="string"),
 *         @OA\Property(property="from", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="last_page_url", type="string"),
 *         @OA\Property(property="next_page_url", type="string", nullable=true),
 *         @OA\Property(property="path", type="string"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="prev_page_url", type="string", nullable=true),
 *         @OA\Property(property="to", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *     ),
 *     @OA\Property(property="message", type="string", example="Speaking tasks retrieved successfully")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingTaskResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="data", ref="#/components/schemas/SpeakingTask"),
 *     @OA\Property(property="message", type="string", example="Speaking task retrieved successfully")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SpeakingSubmissionResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="data", ref="#/components/schemas/SpeakingSubmission"),
 *     @OA\Property(property="message", type="string", example="Speaking submission processed successfully")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         nullable=true,
 *         description="Validation errors (if applicable)"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties={
 *             "type": "array",
 *             "items": {"type": "string"}
 *         },
 *         example={
 *             "title": {"The title field is required."},
 *             "difficulty_level": {"The difficulty level must be one of: beginner, intermediate, advanced."}
 *         }
 *     )
 * )
 */