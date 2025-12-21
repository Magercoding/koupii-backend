<?php

namespace App\Swagger\V1\Listening;

// ===== LISTENING ANALYTICS ENDPOINTS =====

/**
 * @OA\Get(
 *     path="/listening/analytics/tasks/{id}",
 *     tags={"Listening Analytics"},
 *     summary="Get task analytics",
 *     description="Get comprehensive analytics for a specific listening task",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Task UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Task analytics retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="task_id", type="string", format="uuid"),
 *                         @OA\Property(property="total_submissions", type="integer"),
 *                         @OA\Property(property="completed_submissions", type="integer"),
 *                         @OA\Property(property="average_score", type="number", format="float"),
 *                         @OA\Property(property="median_score", type="number", format="float"),
 *                         @OA\Property(property="highest_score", type="number", format="float"),
 *                         @OA\Property(property="lowest_score", type="number", format="float"),
 *                         @OA\Property(property="average_completion_time", type="number", format="float"),
 *                         @OA\Property(property="median_completion_time", type="number", format="float"),
 *                         @OA\Property(property="completion_rate", type="number", format="float"),
 *                         @OA\Property(
 *                             property="question_analytics",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="question_id", type="string"),
 *                                 @OA\Property(property="question_type", type="string"),
 *                                 @OA\Property(property="correct_answers", type="integer"),
 *                                 @OA\Property(property="total_answers", type="integer"),
 *                                 @OA\Property(property="accuracy_rate", type="number", format="float"),
 *                                 @OA\Property(property="average_time_spent", type="number", format="float")
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="difficulty_distribution",
 *                             type="object",
 *                             @OA\Property(property="too_easy", type="number", format="float"),
 *                             @OA\Property(property="appropriate", type="number", format="float"),
 *                             @OA\Property(property="too_hard", type="number", format="float")
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/analytics/students/{id}",
 *     tags={"Listening Analytics"},
 *     summary="Get student analytics",
 *     description="Get comprehensive performance analytics for a specific student",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Student UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="date_from",
 *         in="query",
 *         description="Analytics from date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="date_to",
 *         in="query",
 *         description="Analytics to date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="task_type",
 *         in="query",
 *         description="Filter by task type",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Student analytics retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="student_id", type="string", format="uuid"),
 *                         @OA\Property(property="total_submissions", type="integer"),
 *                         @OA\Property(property="completed_submissions", type="integer"),
 *                         @OA\Property(property="average_score", type="number", format="float"),
 *                         @OA\Property(property="score_trend", type="string", enum={"improving", "declining", "stable"}),
 *                         @OA\Property(property="total_study_time", type="integer", description="Total time in seconds"),
 *                         @OA\Property(property="average_completion_time", type="number", format="float"),
 *                         @OA\Property(
 *                             property="question_type_performance",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="question_type", type="string"),
 *                                 @OA\Property(property="accuracy", type="number", format="float"),
 *                                 @OA\Property(property="attempts", type="integer"),
 *                                 @OA\Property(property="improvement_rate", type="number", format="float")
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="audio_interaction_patterns",
 *                             type="object",
 *                             @OA\Property(property="average_replays", type="number", format="float"),
 *                             @OA\Property(property="total_audio_time", type="number", format="float"),
 *                             @OA\Property(property="pause_frequency", type="number", format="float")
 *                         ),
 *                         @OA\Property(
 *                             property="strengths",
 *                             type="array",
 *                             @OA\Items(type="string")
 *                         ),
 *                         @OA\Property(
 *                             property="areas_for_improvement",
 *                             type="array",
 *                             @OA\Items(type="string")
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/analytics/question-types",
 *     tags={"Listening Analytics"},
 *     summary="Get question type analytics",
 *     description="Get performance analytics across all 15 question types",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="test_id",
 *         in="query",
 *         description="Filter by specific test",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="date_from",
 *         in="query",
 *         description="Analytics from date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="date_to",
 *         in="query",
 *         description="Analytics to date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Question type analytics retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="question_type", type="string", example="QT1"),
 *                             @OA\Property(property="type_name", type="string", example="Multiple Choice"),
 *                             @OA\Property(property="total_questions", type="integer"),
 *                             @OA\Property(property="total_attempts", type="integer"),
 *                             @OA\Property(property="correct_answers", type="integer"),
 *                             @OA\Property(property="accuracy_rate", type="number", format="float"),
 *                             @OA\Property(property="average_time_spent", type="number", format="float"),
 *                             @OA\Property(property="difficulty_rating", type="number", format="float"),
 *                             @OA\Property(
 *                                 property="common_mistakes",
 *                                 type="array",
 *                                 @OA\Items(type="string")
 *                             ),
 *                             @OA\Property(
 *                                 property="performance_trend",
 *                                 type="array",
 *                                 @OA\Items(
 *                                     type="object",
 *                                     @OA\Property(property="date", type="string", format="date"),
 *                                     @OA\Property(property="accuracy", type="number", format="float")
 *                                 )
 *                             )
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/analytics/dashboard",
 *     tags={"Listening Analytics"},
 *     summary="Get dashboard analytics",
 *     description="Get real-time analytics for teacher/admin dashboard",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="class_id",
 *         in="query",
 *         description="Filter by class",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="timeframe",
 *         in="query",
 *         description="Analytics timeframe",
 *         @OA\Schema(type="string", enum={"day", "week", "month", "quarter", "year"}, default="week")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Dashboard data retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(
 *                             property="overview",
 *                             type="object",
 *                             @OA\Property(property="total_tasks", type="integer"),
 *                             @OA\Property(property="active_students", type="integer"),
 *                             @OA\Property(property="completed_submissions", type="integer"),
 *                             @OA\Property(property="pending_submissions", type="integer"),
 *                             @OA\Property(property="average_score", type="number", format="float")
 *                         ),
 *                         @OA\Property(
 *                             property="recent_activity",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="activity_type", type="string"),
 *                                 @OA\Property(property="student_name", type="string"),
 *                                 @OA\Property(property="task_name", type="string"),
 *                                 @OA\Property(property="timestamp", type="string", format="date-time"),
 *                                 @OA\Property(property="score", type="number", format="float")
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="performance_trends",
 *                             type="object",
 *                             @OA\Property(
 *                                 property="score_trend",
 *                                 type="array",
 *                                 @OA\Items(
 *                                     type="object",
 *                                     @OA\Property(property="period", type="string"),
 *                                     @OA\Property(property="average_score", type="number", format="float"),
 *                                     @OA\Property(property="submission_count", type="integer")
 *                                 )
 *                             ),
 *                             @OA\Property(
 *                                 property="engagement_trend",
 *                                 type="array",
 *                                 @OA\Items(
 *                                     type="object",
 *                                     @OA\Property(property="period", type="string"),
 *                                     @OA\Property(property="active_students", type="integer"),
 *                                     @OA\Property(property="study_time", type="number", format="float")
 *                                 )
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="top_performers",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="student_id", type="string"),
 *                                 @OA\Property(property="student_name", type="string"),
 *                                 @OA\Property(property="average_score", type="number", format="float"),
 *                                 @OA\Property(property="completion_rate", type="number", format="float")
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="struggling_students",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="student_id", type="string"),
 *                                 @OA\Property(property="student_name", type="string"),
 *                                 @OA\Property(property="average_score", type="number", format="float"),
 *                                 @OA\Property(property="completion_rate", type="number", format="float"),
 *                                 @OA\Property(property="areas_of_difficulty", type="array", @OA\Items(type="string"))
 *                             )
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/analytics/reports",
 *     tags={"Listening Analytics"},
 *     summary="Generate analytics report",
 *     description="Generate comprehensive analytics report in various formats",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"report_type"},
 *             @OA\Property(
 *                 property="report_type",
 *                 type="string",
 *                 enum={"task_performance", "student_progress", "class_overview", "question_analysis"},
 *                 description="Type of report to generate"
 *             ),
 *             @OA\Property(property="task_id", type="string", format="uuid", description="Specific task (for task_performance)"),
 *             @OA\Property(property="student_id", type="string", format="uuid", description="Specific student (for student_progress)"),
 *             @OA\Property(property="class_id", type="string", format="uuid", description="Specific class (for class_overview)"),
 *             @OA\Property(property="date_from", type="string", format="date", description="Report start date"),
 *             @OA\Property(property="date_to", type="string", format="date", description="Report end date"),
 *             @OA\Property(
 *                 property="format",
 *                 type="string",
 *                 enum={"json", "pdf", "excel"},
 *                 default="json",
 *                 description="Report output format"
 *             ),
 *             @OA\Property(
 *                 property="include_charts",
 *                 type="boolean",
 *                 default=true,
 *                 description="Include visual charts in report"
 *             ),
 *             @OA\Property(
 *                 property="include_recommendations",
 *                 type="boolean",
 *                 default=true,
 *                 description="Include AI-generated recommendations"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Report generated successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="report_id", type="string", format="uuid"),
 *                         @OA\Property(property="download_url", type="string", format="uri"),
 *                         @OA\Property(property="file_size", type="integer"),
 *                         @OA\Property(property="format", type="string"),
 *                         @OA\Property(property="generated_at", type="string", format="date-time"),
 *                         @OA\Property(property="expires_at", type="string", format="date-time")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/analytics/comparative",
 *     tags={"Listening Analytics"},
 *     summary="Get comparative analytics",
 *     description="Compare performance between multiple students",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="student_ids",
 *         in="query",
 *         required=true,
 *         description="Student IDs to compare (2-10 students)",
 *         @OA\Schema(
 *             type="array",
 *             @OA\Items(type="string", format="uuid"),
 *             minItems=2,
 *             maxItems=10
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="metric",
 *         in="query",
 *         description="Primary comparison metric",
 *         @OA\Schema(type="string", enum={"accuracy", "completion_time", "audio_plays", "improvement_rate"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Comparative analytics retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(
 *                             property="students",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="student_id", type="string"),
 *                                 @OA\Property(property="student_name", type="string"),
 *                                 @OA\Property(property="overall_score", type="number", format="float"),
 *                                 @OA\Property(property="rank", type="integer"),
 *                                 @OA\Property(property="strengths", type="array", @OA\Items(type="string")),
 *                                 @OA\Property(property="weaknesses", type="array", @OA\Items(type="string"))
 *                             )
 *                         ),
 *                         @OA\Property(
 *                             property="comparison_metrics",
 *                             type="object",
 *                             @OA\Property(property="metric_name", type="string"),
 *                             @OA\Property(property="highest_performer", type="string"),
 *                             @OA\Property(property="lowest_performer", type="string"),
 *                             @OA\Property(property="average_value", type="number", format="float"),
 *                             @OA\Property(property="standard_deviation", type="number", format="float")
 *                         )
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

