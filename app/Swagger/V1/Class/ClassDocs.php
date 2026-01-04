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
     *     path="/api/v1/classes/create",
     *     operationId="createClass",
     *     tags={"Classes"},
     *     summary="🆕 Create New Class",
     *     description="Create a new class with detailed configuration. Teachers can create classes for their subjects, admins can create any class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Class creation data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "description", "level", "subject"},
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced", description="Clear, descriptive class name"),
     *                 @OA\Property(property="description", type="string", example="Advanced IELTS preparation focusing on academic modules with practice tests and personalized feedback"),
     *                 @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS", description="Subject or exam type"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-001", description="Optional custom code, auto-generated if not provided"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=25, minimum=1, maximum=100),
     *                 @OA\Property(
     *                     property="cover_image",
     *                     type="string",
     *                     format="binary",
     *                     description="Cover image file for the class (JPEG, PNG, max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="string",
     *                     description="JSON string for class schedule configuration",
     *                     example="class_schedule_json"
     *                 ),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="string",
     *                     description="JSON string for class behavior settings",
     *                     example="class_settings_json"
     *                 )
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
     * @OA\Post(
     *     path="/api/v1/classes/update/{id}",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="_method",
     *                     type="string",
     *                     example="PATCH",
     *                     description="HTTP method override for Laravel form method spoofing"
     *                 ),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced Plus", description="Updated class name"),
     *                 @OA\Property(property="description", type="string", example="Enhanced IELTS preparation with personalized feedback"),
     *                 @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-002"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=30, minimum=1, maximum=100),
     *                 @OA\Property(
     *                     property="cover_image",
     *                     type="string",
     *                     format="binary",
     *                     description="Updated cover image file for the class (JPEG, PNG, max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="string",
     *                     description="JSON string for updated class schedule configuration",
     *                     example="updated_schedule_json"
     *                 ),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="string",
     *                     description="JSON string for updated class behavior settings",
     *                     example="updated_settings_json"
     *                 )
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

    /**
     * @OA\Post(
     *     path="/api/v1/classes/join",
     *     operationId="joinClassByCode",
     *     tags={"Classes"},
     *     summary="🎓 Join Class by Code",
     *     description="Allow students to join a class using the unique class code provided by their teacher.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Class code to join",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"class_code"},
     *             @OA\Property(
     *                 property="class_code",
     *                 type="string",
     *                 example="ABC123",
     *                 description="Unique class code to join",
     *                 minLength=3,
     *                 maxLength=20
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Successfully joined class",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successfully joined class: IELTS Preparation Advanced"),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="0199800b-be72-71ed-91bc-118cbfdadc22"),
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation Advanced"),
     *                 @OA\Property(property="description", type="string", example="Advanced IELTS preparation course"),
     *                 @OA\Property(property="class_code", type="string", example="ABC123"),
     *                 @OA\Property(property="teacher", type="string", example="Dr. Sarah Johnson"),
     *                 @OA\Property(property="student_count", type="integer", example=18),
     *                 @OA\Property(property="max_students", type="integer", example=25),
     *                 @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}, example="advanced"),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="enrollment",
     *                     type="object",
     *                     @OA\Property(property="enrolled_at", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", enum={"active", "pending", "suspended"}, example="active")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="❌ Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class is not accepting new enrollments"),
     *             @OA\Property(property="details", type="string", example="This class has reached maximum capacity or is inactive")
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
     *         description="❌ Only students can join classes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Only students can join classes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="⚠️ Already enrolled in this class",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You are already enrolled in this class"),
     *             @OA\Property(
     *                 property="enrollment",
     *                 type="object",
     *                 @OA\Property(property="enrolled_at", type="string", format="date-time"),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Invalid class code or validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="class_code", type="array", @OA\Items(type="string", example="Invalid class code or class not found."))
     *             )
     *         )
     *     )
     * )
     */
    public function joinByCode() {}
}

