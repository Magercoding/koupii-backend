<?php
// filepath: app/Swagger/V1/Class/ClassDocs.php

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
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                     @OA\Property(property="subject", type="string", example="IELTS"),
     *                     @OA\Property(property="class_code", type="string"),
     *                     @OA\Property(property="cover_image", type="string", nullable=true),
     *                     @OA\Property(property="is_active", type="boolean"),
     *                     @OA\Property(property="max_students", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="❌ Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getAllClasses()
    {
    }

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
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced"),
     *                 @OA\Property(property="description", type="string", example="Advanced IELTS preparation course"),
     *                 @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                 @OA\Property(property="subject", type="string", example="IELTS"),
     *                 @OA\Property(property="class_code", type="string", example="IELTS-ADV-001"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="max_students", type="integer", example=25),
     *                 @OA\Property(
     *                     property="cover_image",
     *                     type="string",
     *                     format="binary",
     *                     description="Cover image file for the class (JPEG, PNG, max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="string",
     *                     description="JSON string for class schedule configuration"
     *                 ),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="string",
     *                     description="JSON string for class behavior settings"
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
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="level", type="string"),
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="class_code", type="string"),
     *                 @OA\Property(property="cover_image", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="max_students", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function createClass()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/classes/{id}",
     *     operationId="getClassDetails",
     *     tags={"Classes"},
     *     summary="📋 Get Class Details",
     *     description="Retrieve comprehensive class information including teacher, students, assignments, and analytics.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class UUID identifier",
     *         @OA\Schema(type="string", format="uuid")
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
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="level", type="string"),
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="class_code", type="string"),
     *                 @OA\Property(property="cover_image", type="string", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="max_students", type="integer"),
     *                 @OA\Property(property="current_students_count", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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
    public function getClassDetail()
    {
    }

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
     *         @OA\Schema(type="string", format="uuid")
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
     *                 @OA\Property(property="name", type="string", example="IELTS Preparation - Advanced Plus"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="level", type="string", enum={"beginner", "intermediate", "advanced"}),
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="class_code", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="max_students", type="integer"),
     *                 @OA\Property(
     *                     property="cover_image",
     *                     type="string",
     *                     format="binary",
     *                     description="Updated cover image file for the class"
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="string",
     *                     description="JSON string for updated class schedule"
     *                 ),
     *                 @OA\Property(
     *                     property="settings",
     *                     type="string",
     *                     description="JSON string for updated class settings"
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
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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
    public function updateClass()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/classes/{id}",
     *     operationId="deleteClass",
     *     tags={"Classes"},
     *     summary="🗑️ Delete Class",
     *     description="Permanently delete a class and all associated data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Class UUID identifier",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Class deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Class deleted successfully")
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
    public function deleteClass()
    {
    }

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
     *                 description="Unique class code to join"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Successfully joined class",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successfully joined class"),
     *             @OA\Property(
     *                 property="class",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="class_code", type="string"),
     *                 @OA\Property(property="teacher", type="string"),
     *                 @OA\Property(property="student_count", type="integer"),
     *                 @OA\Property(property="max_students", type="integer"),
     *                 @OA\Property(property="level", type="string"),
     *                 @OA\Property(property="subject", type="string"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="❌ Invalid class code",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid class code or class not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="⚠️ Already enrolled",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You are already enrolled in this class")
     *         )
     *     )
     * )
     */
    public function joinByCode()
    {
    }
}