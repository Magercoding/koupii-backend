<?php

namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/classes",
     *     operationId="getAllClasses",
     *     tags={"Classes"},
     *     summary="📚 Get All Classes",
     *     description="Retrieve all classes. Teachers see their own classes with management options, students see enrolled classes with assignment info.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role_view",
     *         in="query",
     *         description="View mode based on user role",
     *         required=false,
     *         @OA\Schema(type="string", enum={"teacher", "student", "admin"})
     *     ),
     *     @OA\Parameter(
     *         name="include_stats",
     *         in="query",
     *         description="Include class statistics",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Classes retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Classes retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *                     @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced"),
     *                     @OA\Property(property="description", type="string", example="Advanced IELTS preparation focusing on academic modules"),
     *                     @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *                     @OA\Property(property="subject", type="string", example="IELTS"),
     *                     @OA\Property(property="class_code", type="string", example="IELTS-ADV-001"),
     *                     @OA\Property(property="cover_image", type="string", nullable=true, example="https://api.koupii.com/storage/classes/cover-1.jpg"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="max_students", type="integer", example=25),
     *                     @OA\Property(property="schedule", type="object",
     *                         @OA\Property(property="days", type="array", @OA\Items(type="string"), example={"monday", "wednesday", "friday"}),
     *                         @OA\Property(property="time", type="string", example="14:00"),
     *                         @OA\Property(property="duration_minutes", type="integer", example=90),
     *                         @OA\Property(property="timezone", type="string", example="UTC+7")
     *                     ),
     *                     @OA\Property(
     *                         property="teacher",
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string", example="Dr. Sarah Wilson"),
     *                         @OA\Property(property="email", type="string", example="sarah.wilson@school.edu"),
     *                         @OA\Property(property="bio", type="string", example="IELTS expert with 10+ years experience"),
     *                         @OA\Property(property="avatar", type="string", nullable=true, example="https://api.koupii.com/storage/avatars/teacher-1.jpg"),
     *                         @OA\Property(property="specializations", type="array", @OA\Items(type="string"), example={"IELTS", "TOEFL", "Academic English"}),
     *                         @OA\Property(property="rating", type="number", format="float", example=4.8)
     *                     ),
     *                     @OA\Property(
     *                         property="stats",
     *                         type="object",
     *                         @OA\Property(property="total_students", type="integer", example=18),
     *                         @OA\Property(property="active_students", type="integer", example=16),
     *                         @OA\Property(property="total_assignments", type="integer", example=24),
     *                         @OA\Property(property="pending_assignments", type="integer", example=5),
     *                         @OA\Property(property="average_completion_rate", type="number", format="float", example=87.5),
     *                         @OA\Property(property="average_score", type="number", format="float", example=78.2),
     *                         @OA\Property(property="improvement_trend", type="string", enum={"improving", "stable", "declining"}, example="improving")
     *                     ),
     *                     @OA\Property(
     *                         property="students_preview",
     *                         type="array",
     *                         description="First 5 students for preview",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="string", format="uuid"),
     *                             @OA\Property(property="name", type="string", example="Alice Johnson"),
     *                             @OA\Property(property="avatar", type="string", nullable=true),
     *                             @OA\Property(property="current_level", type="string", example="6.5"),
     *                             @OA\Property(property="target_score", type="string", example="7.0"),
     *                             @OA\Property(property="progress_percentage", type="number", format="float", example=75.5),
     *                             @OA\Property(property="last_active", type="string", format="date-time")
     *                         )
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_classes", type="integer", example=6),
     *                 @OA\Property(property="total_students", type="integer", example=89),
     *                 @OA\Property(property="active_assignments", type="integer", example=15),
     *                 @OA\Property(property="completion_rate", type="number", format="float", example=85.7)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You don't have permission to access this resource.")
     *         )
     *     )
     * )
     */
    public function getAllClasses() {}

    /**
     * @OA\Post(
     *     path="/api/v1/classes",
     *     operationId="createClass",
     *     tags={"Classes"},
     *     summary="🆕 Create New Class",
     *     description="Create a new class with detailed configuration. Teachers can create classes for their subjects, admins can create any class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Class creation data",
     *         @OA\JsonContent(
     *             required={"name", "description", "level", "subject"},
     *             @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced", description="Clear, descriptive class name"),
     *             @OA\Property(property="description", type="string", example="Advanced IELTS preparation focusing on academic modules with practice tests and personalized feedback"),
     *             @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *             @OA\Property(property="subject", type="string", example="IELTS", description="Subject or exam type"),
     *             @OA\Property(property="class_code", type="string", example="IELTS-ADV-001", description="Optional custom code, auto-generated if not provided"),
     *             @OA\Property(property="cover_image_url", type="string", format="uri", example="https://api.koupii.com/storage/classes/cover-1.jpg", description="Optional cover image URL"),
     *             @OA\Property(property="is_active", type="boolean", example=true, default=true),
     *             @OA\Property(property="max_students", type="integer", example=25, minimum=1, maximum=100),
     *             @OA\Property(
     *                 property="schedule",
     *                 type="object",
     *                 description="Class schedule configuration",
     *                 @OA\Property(property="days", type="array", @OA\Items(type="string", enum={"monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"}), example={"monday", "wednesday", "friday"}),
     *                 @OA\Property(property="time", type="string", format="time", example="14:00", description="Class start time"),
     *                 @OA\Property(property="duration_minutes", type="integer", example=90, minimum=30, maximum=240),
     *                 @OA\Property(property="timezone", type="string", example="UTC+7", description="Class timezone")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 description="Class behavior settings",
     *                 @OA\Property(property="auto_enroll", type="boolean", example=false, description="Allow students to auto-enroll with class code"),
     *                 @OA\Property(property="require_approval", type="boolean", example=true, description="Require teacher approval for enrollment"),
     *                 @OA\Property(property="show_leaderboard", type="boolean", example=true, description="Display class leaderboard to students"),
     *                 @OA\Property(property="allow_late_submission", type="boolean", example=true, description="Allow assignments after due date"),
     *                 @OA\Property(property="late_penalty_percent", type="integer", example=10, minimum=0, maximum=100)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="✅ Class created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced"),
     *                 @OA\Property(property="description", type="string", example="Advanced IELTS preparation focusing on academic modules"),
     *                 @OA\Property(property="level", type="string", example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-001"),
     *                 @OA\Property(property="cover_image", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=25),
     *                 @OA\Property(property="current_students_count", type="integer", example=0),
     *                 @OA\Property(property="teacher_id", type="string", format="uuid"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="❌ Bad Request - Invalid data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid schedule configuration"),
     *             @OA\Property(property="errors", type="object", 
     *                 @OA\Property(property="schedule.time", type="array", @OA\Items(type="string", example="The time field must be a valid time format."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Only teachers and administrators can create classes.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="⚠️ Conflict - Class already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A class with this name or code already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="max_students", type="array", @OA\Items(type="string", example="The max students must be between 1 and 100."))
     *             )
     *         )
     *     )
     * )
     */
    public function createClass() {}

    /**
     * @OA\Get(
     *     path="/api/v1/classes/{id}",
     *     operationId="getClassDetails",
     *     tags={"Classes"},
     *     summary="📋 Get Class Details",
     *     description="Retrieve comprehensive class information including teacher, students, assignments, and analytics. Response varies based on user role and permissions.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class UUID identifier",
     *         @OA\Schema(type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Additional data to include",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string", enum={"students", "assignments", "analytics", "upcoming_tasks", "recent_activity"}),
     *             default={"students", "assignments"}
     *         ),
     *         style="form",
     *         explode=false
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Class details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class details retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced"),
     *                 @OA\Property(property="description", type="string", example="Advanced IELTS preparation focusing on academic modules"),
     *                 @OA\Property(property="level", type="string", example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-001"),
     *                 @OA\Property(property="cover_image", type="string", nullable=true, example="https://api.koupii.com/storage/classes/cover-1.jpg"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=25),
     *                 @OA\Property(property="current_students_count", type="integer", example=18),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="object",
     *                     @OA\Property(property="days", type="array", @OA\Items(type="string"), example={"monday", "wednesday", "friday"}),
     *                     @OA\Property(property="time", type="string", example="14:00"),
     *                     @OA\Property(property="duration_minutes", type="integer", example=90),
     *                     @OA\Property(property="timezone", type="string", example="UTC+7")
     *                 ),
     *                 @OA\Property(
     *                     property="teacher",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Dr. Sarah Wilson"),
     *                     @OA\Property(property="email", type="string", example="sarah.wilson@school.edu"),
     *                     @OA\Property(property="bio", type="string", example="IELTS expert with 10+ years experience"),
     *                     @OA\Property(property="avatar", type="string", nullable=true, example="https://api.koupii.com/storage/avatars/teacher-1.jpg"),
     *                     @OA\Property(property="specializations", type="array", @OA\Items(type="string"), example={"IELTS", "TOEFL", "Academic English"}),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.8),
     *                     @OA\Property(property="total_classes", type="integer", example=12),
     *                     @OA\Property(property="total_students", type="integer", example=245)
     *                 ),
     *                 @OA\Property(
     *                     property="students",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="name", type="string", example="Alice Johnson"),
     *                         @OA\Property(property="avatar", type="string", nullable=true),
     *                         @OA\Property(property="enrollment_date", type="string", format="date-time"),
     *                         @OA\Property(property="current_level", type="string", example="6.5"),
     *                         @OA\Property(property="target_score", type="string", example="7.0"),
     *                         @OA\Property(property="progress_percentage", type="number", format="float", example=75.5),
     *                         @OA\Property(property="assignments_completed", type="integer", example=12),
     *                         @OA\Property(property="assignments_total", type="integer", example=15),
     *                         @OA\Property(property="average_score", type="number", format="float", example=82.3),
     *                         @OA\Property(property="last_active", type="string", format="date-time"),
     *                         @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="assignments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="title", type="string", example="Academic Writing Task 1"),
     *                         @OA\Property(property="task_type", type="string", enum={"writing", "reading", "listening", "speaking"}, example="writing"),
     *                         @OA\Property(property="due_date", type="string", format="date-time"),
     *                         @OA\Property(property="status", type="string", enum={"active", "completed", "overdue"}, example="active"),
     *                         @OA\Property(property="submissions_count", type="integer", example=15),
     *                         @OA\Property(property="total_students", type="integer", example=18),
     *                         @OA\Property(property="average_score", type="number", format="float", example=78.5)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="analytics",
     *                     type="object",
     *                     @OA\Property(property="completion_rate", type="number", format="float", example=87.5),
     *                     @OA\Property(property="average_score", type="number", format="float", example=78.2),
     *                     @OA\Property(property="improvement_trend", type="string", enum={"improving", "stable", "declining"}, example="improving"),
     *                     @OA\Property(property="most_challenging_task_type", type="string", example="speaking"),
     *                     @OA\Property(property="best_performing_task_type", type="string", example="reading")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Forbidden - No access to this class",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You don't have permission to view this class.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Class not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class not found.")
     *         )
     *     )
     * )
     */
    public function getClassDetail() {}

    /**
     * @OA\Put(
     *     path="/api/v1/classes/{id}",
     *     operationId="updateClass",
     *     tags={"Classes"},
     *     summary="✏️ Update Class",
     *     description="Update class information. Teachers can only update their own classes, administrators can update any class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class UUID identifier",
     *         @OA\Schema(type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated class data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced Plus", description="Updated class name"),
     *             @OA\Property(property="description", type="string", example="Enhanced IELTS preparation with personalized feedback"),
     *             @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *             @OA\Property(property="subject", type="string", example="IELTS"),
     *             @OA\Property(property="class_code", type="string", example="IELTS-ADV-002"),
     *             @OA\Property(property="cover_image_url", type="string", format="uri", example="https://api.koupii.com/storage/classes/new-cover.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="max_students", type="integer", example=30, minimum=1, maximum=100),
     *             @OA\Property(
     *                 property="schedule",
     *                 type="object",
     *                 @OA\Property(property="days", type="array", @OA\Items(type="string"), example={"tuesday", "thursday"}),
     *                 @OA\Property(property="time", type="string", format="time", example="15:30"),
     *                 @OA\Property(property="duration_minutes", type="integer", example=120),
     *                 @OA\Property(property="timezone", type="string", example="UTC+7")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 @OA\Property(property="auto_enroll", type="boolean", example=false),
     *                 @OA\Property(property="require_approval", type="boolean", example=true),
     *                 @OA\Property(property="show_leaderboard", type="boolean", example=true),
     *                 @OA\Property(property="allow_late_submission", type="boolean", example=false),
     *                 @OA\Property(property="late_penalty_percent", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Class updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced Plus"),
     *                 @OA\Property(property="description", type="string", example="Enhanced IELTS preparation with personalized feedback"),
     *                 @OA\Property(property="level", type="string", example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-002"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=30),
     *                 @OA\Property(property="current_students_count", type="integer", example=18),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Forbidden - Cannot update this class",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You can only update your own classes.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Class not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="⚠️ Conflict - Duplicate data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A class with this name or code already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="max_students", type="array", @OA\Items(type="string", example="Cannot reduce max students below current enrollment count."))
     *             )
     *         )
     *     )
     * )
     */
    public function updateClass() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/classes/{id}",
     *     operationId="deleteClass",
     *     tags={"Classes"},
     *     summary="🗑️ Delete Class",
     *     description="Permanently delete a class and all associated data. Teachers can only delete their own classes, administrators can delete any class. This action cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class UUID identifier",
     *         @OA\Schema(type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22")
     *     ),
     *     @OA\Parameter(
     *         name="force",
     *         in="query",
     *         description="Force delete even with active assignments",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="archive_data",
     *         in="query",
     *         description="Archive student data before deletion",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Class deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class deleted successfully"),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="class_id", type="string", format="uuid"),
     *                 @OA\Property(property="class_name", type="string", example="IELTS Preparation - Advanced"),
     *                 @OA\Property(property="students_affected", type="integer", example=18),
     *                 @OA\Property(property="assignments_removed", type="integer", example=24),
     *                 @OA\Property(property="data_archived", type="boolean", example=true),
     *                 @OA\Property(property="archive_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="❌ Forbidden - Cannot delete this class",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You can only delete your own classes.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="❌ Class not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Class not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="⚠️ Cannot delete - Active dependencies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete class with active assignments. Use force=true to override."),
     *             @OA\Property(
     *                 property="dependencies",
     *                 type="object",
     *                 @OA\Property(property="active_assignments", type="integer", example=5),
     *                 @OA\Property(property="pending_submissions", type="integer", example=12),
     *                 @OA\Property(property="enrolled_students", type="integer", example=18)
     *             )
     *         )
     *     )
     * )
     */
    public function deleteClass() {}
}

