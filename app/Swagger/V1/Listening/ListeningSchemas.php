<?php

namespace App\Swagger\V1\Listening;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Koupii Listening Module API",
 *     description="Comprehensive API documentation for listening module with 15 question types",
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
 *     name="Listening Tasks",
 *     description="Manage listening tasks and exercises"
 * )
 */

/**
 * @OA\Tag(
 *     name="Listening Questions",
 *     description="Manage listening questions (15 question types)"
 * )
 */

/**
 * @OA\Tag(
 *     name="Listening Audio",
 *     description="Audio file management and processing"
 * )
 */

/**
 * @OA\Tag(
 *     name="Listening Submissions",
 *     description="Student submission management"
 * )
 */

/**
 * @OA\Tag(
 *     name="Listening Answers",
 *     description="Student answer management and grading"
 * )
 */

/**
 * @OA\Tag(
 *     name="Listening Analytics",
 *     description="Analytics and reporting for listening module"
 * )
 */

// ===== SCHEMAS =====

/**
 * @OA\Schema(
 *     schema="ListeningTask",
 *     type="object",
 *     required={"title", "audio_url", "question_types", "difficulty_level"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Task UUID"),
 *     @OA\Property(property="title", type="string", maxLength=255, description="Task title"),
 *     @OA\Property(property="description", type="string", description="Task description"),
 *     @OA\Property(property="audio_url", type="string", format="uri", description="Audio file URL"),
 *     @OA\Property(property="transcript", type="string", description="Audio transcript"),
 *     @OA\Property(
 *         property="question_types", 
 *         type="array", 
 *         @OA\Items(
 *             type="string", 
 *             enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"}
 *         ),
 *         description="Supported question types"
 *     ),
 *     @OA\Property(
 *         property="difficulty_level", 
 *         type="string", 
 *         enum={"beginner", "intermediate", "advanced"}, 
 *         description="Task difficulty"
 *     ),
 *     @OA\Property(property="duration", type="integer", description="Audio duration in seconds"),
 *     @OA\Property(property="max_replays", type="integer", description="Maximum audio replays allowed"),
 *     @OA\Property(property="replay_controls", type="object", description="Audio replay control settings"),
 *     @OA\Property(property="is_published", type="boolean", description="Publication status"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ListeningQuestion",
 *     type="object",
 *     required={"question_text", "question_type", "correct_answer"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Question UUID"),
 *     @OA\Property(property="question_text", type="string", description="Question text"),
 *     @OA\Property(
 *         property="question_type", 
 *         type="string", 
 *         enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"},
 *         description="Question type"
 *     ),
 *     @OA\Property(property="options", type="object", description="Question options (for multiple choice)"),
 *     @OA\Property(property="correct_answer", type="object", description="Correct answer data"),
 *     @OA\Property(property="audio_segment", type="object", description="Associated audio segment"),
 *     @OA\Property(property="time_limit", type="integer", description="Time limit in seconds"),
 *     @OA\Property(property="points", type="integer", description="Question points"),
 *     @OA\Property(property="order", type="integer", description="Question order"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ListeningSubmission",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Submission UUID"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="Student UUID"),
 *     @OA\Property(property="listening_task_id", type="string", format="uuid", description="Task UUID"),
 *     @OA\Property(property="score", type="number", format="float", description="Total score"),
 *     @OA\Property(property="max_score", type="integer", description="Maximum possible score"),
 *     @OA\Property(property="completion_time", type="integer", description="Time taken in seconds"),
 *     @OA\Property(property="audio_plays", type="integer", description="Total audio plays"),
 *     @OA\Property(
 *         property="status", 
 *         type="string", 
 *         enum={"in_progress", "submitted", "graded"}, 
 *         description="Submission status"
 *     ),
 *     @OA\Property(property="answers", type="array", @OA\Items(ref="#/components/schemas/ListeningAnswer")),
 *     @OA\Property(property="feedback", type="string", description="Teacher feedback"),
 *     @OA\Property(property="submitted_at", type="string", format="date-time"),
 *     @OA\Property(property="graded_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ListeningAnswer",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Answer UUID"),
 *     @OA\Property(property="question_id", type="string", format="uuid", description="Question UUID"),
 *     @OA\Property(property="user_answer", type="object", description="Student's answer"),
 *     @OA\Property(property="correct_answer", type="object", description="Correct answer"),
 *     @OA\Property(property="is_correct", type="boolean", description="Answer correctness"),
 *     @OA\Property(property="score", type="number", format="float", description="Answer score"),
 *     @OA\Property(property="feedback", type="string", description="Answer feedback"),
 *     @OA\Property(property="time_spent", type="integer", description="Time spent on question in seconds"),
 *     @OA\Property(property="attempts", type="integer", description="Number of attempts")
 * )
 */

/**
 * @OA\Schema(
 *     schema="AudioSegment",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", description="Segment UUID"),
 *     @OA\Property(property="start_time", type="number", format="float", description="Start time in seconds"),
 *     @OA\Property(property="end_time", type="number", format="float", description="End time in seconds"),
 *     @OA\Property(property="duration", type="number", format="float", description="Segment duration"),
 *     @OA\Property(property="transcript", type="string", description="Segment transcript"),
 *     @OA\Property(property="label", type="string", description="Segment label"),
 *     @OA\Property(property="is_key_segment", type="boolean", description="Is this a key segment"),
 *     @OA\Property(property="order", type="integer", description="Segment order")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", description="Error message"),
 *     @OA\Property(
 *         property="errors", 
 *         type="object",
 *         additionalProperties={
 *             "type": "array",
 *             @OA\Items(type="string")
 *         },
 *         description="Validation error details"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", description="Success message"),
 *     @OA\Property(property="data", type="object", description="Response data")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", description="Error message")
 * )
 */

// ===== RESPONSE SCHEMAS =====

/**
 * @OA\Schema(
 *     schema="ListeningTaskResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="data",
 *                 ref="#/components/schemas/ListeningTask"
 *             )
 *         )
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="ListeningTaskCollection",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ListeningTask")
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer"),
 *                 @OA\Property(property="per_page", type="integer"),
 *                 @OA\Property(property="total", type="integer"),
 *                 @OA\Property(property="last_page", type="integer")
 *             )
 *         )
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="QuestionTypesResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="question_types",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="type", type="string", example="QT1"),
 *                         @OA\Property(property="name", type="string", example="Multiple Choice"),
 *                         @OA\Property(property="description", type="string"),
 *                         @OA\Property(property="validation_rules", type="object"),
 *                         @OA\Property(property="template", type="object")
 *                     )
 *                 )
 *             )
 *         )
 *     }
 * )
 */

