<?php

namespace App\Swagger\V1\Listening;

// ===== LISTENING QUESTIONS ENDPOINTS =====

/**
 * @OA\Get(
 *     path="/listening/questions",
 *     tags={"Listening Questions"},
 *     summary="Get listening questions list",
 *     description="Retrieve paginated list of listening questions with filters",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="task_id",
 *         in="query",
 *         description="Filter by task ID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Parameter(
 *         name="question_type",
 *         in="query",
 *         description="Filter by question type",
 *         @OA\Schema(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"})
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         @OA\Schema(type="integer", minimum=1, default=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(ref="#/components/schemas/ListeningQuestion")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/questions",
 *     tags={"Listening Questions"},
 *     summary="Create new listening question",
 *     description="Create a new listening question with specified type and validation",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"task_id", "question_text", "question_type", "correct_answer"},
 *             @OA\Property(property="task_id", type="string", format="uuid", description="Associated task ID"),
 *             @OA\Property(property="question_text", type="string", description="Question text", example="What is the main topic of the conversation?"),
 *             @OA\Property(
 *                 property="question_type", 
 *                 type="string", 
 *                 enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"},
 *                 example="QT1"
 *             ),
 *             @OA\Property(
 *                 property="options",
 *                 type="object",
 *                 description="Question options (varies by type)",
 *                 example={
 *                     "A": "Travel arrangements",
 *                     "B": "Business meeting",
 *                     "C": "Academic discussion",
 *                     "D": "Personal conversation"
 *                 }
 *             ),
 *             @OA\Property(
 *                 property="correct_answer",
 *                 type="object",
 *                 description="Correct answer (varies by type)",
 *                 example={"selected": "C", "explanation": "The conversation focuses on academic research"}
 *             ),
 *             @OA\Property(
 *                 property="audio_segment",
 *                 type="object",
 *                 description="Associated audio segment",
 *                 @OA\Property(property="start_time", type="number", example=30.5),
 *                 @OA\Property(property="end_time", type="number", example=95.2),
 *                 @OA\Property(property="transcript", type="string")
 *             ),
 *             @OA\Property(property="time_limit", type="integer", example=60, description="Time limit in seconds"),
 *             @OA\Property(property="points", type="integer", example=1, description="Points for correct answer"),
 *             @OA\Property(property="order", type="integer", example=1, description="Question order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Question created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/ListeningQuestion")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/questions/types/supported",
 *     tags={"Listening Questions"},
 *     summary="Get supported question types",
 *     description="Retrieve all supported listening question types with their descriptions and validation rules",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Supported question types retrieved successfully",
 *         @OA\JsonContent(ref="#/components/schemas/QuestionTypesResponse")
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/questions/types/{questionType}/template",
 *     tags={"Listening Questions"},
 *     summary="Get question template",
 *     description="Get template structure for specific question type",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="questionType",
 *         in="path",
 *         required=true,
 *         description="Question type",
 *         @OA\Schema(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Template retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="question_type", type="string", example="QT1"),
 *                         @OA\Property(property="name", type="string", example="Multiple Choice"),
 *                         @OA\Property(property="description", type="string"),
 *                         @OA\Property(property="template", type="object"),
 *                         @OA\Property(property="validation_rules", type="object"),
 *                         @OA\Property(property="example", type="object")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/questions/types/{questionType}/validate",
 *     tags={"Listening Questions"},
 *     summary="Validate question data",
 *     description="Validate question data against specific question type rules",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="questionType",
 *         in="path",
 *         required=true,
 *         description="Question type",
 *         @OA\Schema(type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"})
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="question_data", type="object", description="Question data to validate")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Validation successful",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="is_valid", type="boolean"),
 *                         @OA\Property(property="errors", type="array", @OA\Items(type="string")),
 *                         @OA\Property(property="warnings", type="array", @OA\Items(type="string"))
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/listening/questions/bulk/create",
 *     tags={"Listening Questions"},
 *     summary="Bulk create questions",
 *     description="Create multiple listening questions at once",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="questions",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="task_id", type="string", format="uuid"),
 *                     @OA\Property(property="question_text", type="string"),
 *                     @OA\Property(property="question_type", type="string", enum={"QT1", "QT2", "QT3", "QT4", "QT5", "QT6", "QT7", "QT8", "QT9", "QT10", "QT11", "QT12", "QT13", "QT14", "QT15"}),
 *                     @OA\Property(property="options", type="object"),
 *                     @OA\Property(property="correct_answer", type="object"),
 *                     @OA\Property(property="points", type="integer"),
 *                     @OA\Property(property="order", type="integer")
 *                 )
 *             ),
 *             @OA\Property(property="validate_all", type="boolean", default=true, description="Validate all questions before creating")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Questions created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="created_count", type="integer"),
 *                         @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/ListeningQuestion")),
 *                         @OA\Property(property="errors", type="array", @OA\Items(type="object"))
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/listening/questions/{id}/preview",
 *     tags={"Listening Questions"},
 *     summary="Preview question",
 *     description="Get question preview with rendered content and validation",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Question UUID",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Question preview retrieved successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="question", ref="#/components/schemas/ListeningQuestion"),
 *                         @OA\Property(property="rendered_content", type="object"),
 *                         @OA\Property(property="validation_status", type="object"),
 *                         @OA\Property(property="audio_segment", ref="#/components/schemas/AudioSegment")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */