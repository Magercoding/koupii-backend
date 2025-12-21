<?php

namespace App\Swagger\V1\Listening;

// ===== LISTENING SUBMISSIONS ENDPOINTS =====

/**
 * @OA\Get(
 *     path="/listening/submissions",
 *     tags={"Listening Submissions"},
 *     summary="Get submissions list",
 *     description="Retrieve paginated list of listening submissions with filters",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="task_id",
 *         in="query",
 *         description="Filter by task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="student_id",
 *         in="query",
 *         description="Filter by student ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by submission status",
 *         @OA\Schema(type="string", enum={"in_progress", "submitted", "graded"})
 *     ),
 *     @OA\Parameter(
 *         name="date_from",
 *         in="query",
 *         description="Filter submissions from date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="date_to",
 *         in="query",
 *         description="Filter submissions to date",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Submissions retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(ref="#/components/schemas/ListeningSubmission")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/submissions",
 *     tags={"Listening Submissions"},
 *     summary="Create new submission",
 *     description="Start a new listening task submission",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"listening_task_id"},
 *             @OA\Property(property="listening_task_id", type="string", format="uuid", description="Task UUID"),
 *             @OA\Property(property="assignment_id", type="string", format="uuid", description="Assignment UUID (optional)")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Submission created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/ListeningSubmission")
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/submissions/{id}",
 *     tags={"Listening Submissions"},
 *     summary="Get submission details",
 *     description="Retrieve detailed submission information with answers",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="include",
 *         in="query",
 *         description="Include related data",
 *         @OA\Schema(type="string", enum={"answers", "task", "audio_logs", "analytics"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Submission retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/ListeningSubmission")
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/submissions/{id}/submit",
 *     tags={"Listening Submissions"},
 *     summary="Submit answers",
 *     description="Submit final answers for grading",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="force_submit", type="boolean", default=false, description="Force submit even if incomplete"),
 *             @OA\Property(property="final_check", type="boolean", default=true, description="Perform final validation check")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Submission completed successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="submission", ref="#/components/schemas/ListeningSubmission"),
 *                         @OA\Property(property="score", type="number", format="float"),
 *                         @OA\Property(property="completion_time", type="integer"),
 *                         @OA\Property(property="auto_graded", type="boolean")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/submissions/{id}/grade",
 *     tags={"Listening Submissions"},
 *     summary="Grade submission",
 *     description="Grade a submitted listening task (teacher only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="manual_grades", type="object", description="Manual grades for specific questions"),
 *             @OA\Property(property="feedback", type="string", description="Overall feedback"),
 *             @OA\Property(property="bonus_points", type="number", format="float", description="Bonus points"),
 *             @OA\Property(property="penalty_points", type="number", format="float", description="Penalty points"),
 *             @OA\Property(
 *                 property="question_feedback",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="question_id", type="string", format="uuid"),
 *                     @OA\Property(property="feedback", type="string"),
 *                     @OA\Property(property="score_override", type="number", format="float")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Submission graded successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/ListeningSubmission")
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Patch(
 *     path="/listening/submissions/{id}/autosave",
 *     tags={"Listening Submissions"},
 *     summary="Auto-save progress",
 *     description="Auto-save submission progress without submitting",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="answers",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="question_id", type="string", format="uuid"),
 *                     @OA\Property(property="answer", type="object"),
 *                     @OA\Property(property="is_final", type="boolean", default=false)
 *                 )
 *             ),
 *             @OA\Property(property="progress_percentage", type="number", format="float", minimum=0, maximum=100)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Progress saved successfully",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/submissions/{id}/analysis",
 *     tags={"Listening Submissions"},
 *     summary="Get submission analysis",
 *     description="Get detailed analysis of submission performance",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Analysis retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="overall_score", type="number", format="float"),
 *                         @OA\Property(property="score_percentage", type="number", format="float"),
 *                         @OA\Property(property="completion_time", type="integer"),
 *                         @OA\Property(property="questions_correct", type="integer"),
 *                         @OA\Property(property="questions_total", type="integer"),
 *                         @OA\Property(property="audio_interaction_stats", type="object"),
 *                         @OA\Property(
 *                             property="question_type_analysis",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="question_type", type="string"),
 *                                 @OA\Property(property="correct", type="integer"),
 *                                 @OA\Property(property="total", type="integer"),
 *                                 @OA\Property(property="accuracy", type="number", format="float")
 *                             )
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
 *     path="/listening/submissions/{id}/export",
 *     tags={"Listening Submissions"},
 *     summary="Export submission",
 *     description="Export submission data in various formats",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Submission UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="Export format",
 *         @OA\Schema(type="string", enum={"json", "pdf", "csv"}, default="json")
 *     ),
 *     @OA\Parameter(
 *         name="include",
 *         in="query",
 *         description="Data to include",
 *         @OA\Schema(
 *             type="array",
 *             @OA\Items(type="string", enum={"answers", "audio_logs", "analytics", "feedback"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Export created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="download_url", type="string", format="uri"),
 *                         @OA\Property(property="file_size", type="integer"),
 *                         @OA\Property(property="format", type="string"),
 *                         @OA\Property(property="expires_at", type="string", format="date-time")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

