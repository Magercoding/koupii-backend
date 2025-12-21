<?php


namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassEnrollmentDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/enrollments",
     *     tags={"Enrollments"},
     *     summary="Get all enrollments based on user role",
     *     description="Admin sees all enrollments, teacher sees only enrollments in their classes, student sees only their enrolled classes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of enrollments",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", example="d40a0147-493f-4d70-bb4a-d7052e89e921"),
     *                 @OA\Property(property="class_id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *                 @OA\Property(property="student_id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="enrolled_at", type="string", example="2025-07-17 08:55:35"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-17T08:55:35.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T08:55:35.000000Z"),
     *                 @OA\Property(
     *                     property="class",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *                     @OA\Property(property="teacher_id", type="string", example="598d528e-c734-456e-b77c-7abee4cf92fa"),
     *                     @OA\Property(property="name", type="string", example="Theorytical Class"),
     *                     @OA\Property(property="description", type="string", example="This is theory class"),
     *                     @OA\Property(property="class_code", type="string", example="lkoiun"),
     *                     @OA\Property(property="cover_image", type="string", example="cover.jpg"),
     *                     @OA\Property(property="is_active", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(
     *                     property="student",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                     @OA\Property(property="name", type="string", example="Student User 2"),
     *                     @OA\Property(property="email", type="string", example="student2@example.com"),
     *                     @OA\Property(property="role", type="string", example="student")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllEnrollments() {}

    /**
     * @OA\Post(
     *     path="/api/v1/enrollments/create",
     *     tags={"Enrollments"},
     *     summary="Enroll student to class",
     *     description="Student enrolls to a class using class_id and class_code.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_code"},
     *             @OA\Property(property="class_code", type="string", example="1234567"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Enrolled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrolled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="d40a0147-493f-4d70-bb4a-d7052e89e921"),
     *                 @OA\Property(property="class_id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="enrolled_at", type="string", example="2025-07-17T08:55:35.710993Z"),
     *                 @OA\Property(property="created_at", type="string", example="2025-07-17T08:55:35.710993Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-07-17T08:55:35.710993Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid class code or validation error")
     * )
     */
    public function enrollStudent() {}

    /**
     * @OA\Get(
     *     path="/api/v1/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="Get enrollment details",
     *     description="Show enrollment details including class and student info.",
     *     security={{"bearerAuth":{}}},
*     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Enrollment ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="d40a0147-493f-4d70-bb4a-d7052e89e921"),
     *             @OA\Property(property="class_id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *             @OA\Property(property="student_id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="enrolled_at", type="string", example="2025-07-17 08:55:35"),
     *             @OA\Property(property="class", type="object",
     *                 @OA\Property(property="id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *                 @OA\Property(property="name", type="string", example="Theorytical Class")
     *             ),
     *             @OA\Property(property="student", type="object",
     *                 @OA\Property(property="id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                 @OA\Property(property="name", type="string", example="Student User 2")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Enrollment not found")
     * )
     */
    public function getEnrollmentDetail() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/enrollments/update/{id}",
     *     tags={"Enrollments"},
     *     summary="Update enrollment status",
     *     description="Admin, teacher, or student can update enrollment (depends on role).",
     *     security={{"bearerAuth":{}}},
*     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="class_id", type="string", example="20d7c432-467b-433a-b035-b43481b5ee85"),
     *             @OA\Property(property="status", type="string", example="inactive"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="d40a0147-493f-4d70-bb4a-d7052e89e921"),
     *                 @OA\Property(property="status", type="string", example="inactive"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T09:00:46.000000Z")
     *             )
     *         )
     *     )
     * )
     */
    public function updateEnrollment() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/enrollments/delete/{id}",
     *     tags={"Enrollments"},
     *     summary="Delete enrollment",
     *     description="Admin, teacher, or student can delete enrollment based on role authorization.",
     *     security={{"bearerAuth":{}}},
*     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function deleteEnrollment() {}
}

