<?php

namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassInvitationDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/class/invitations",
     *     tags={"Invitations"},
     *     summary="Get all invitations based on user role",
     *     description="Admin sees all invitations, teacher sees only invitations in their classes, student sees only invitations sent to them.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="X-XSRF-TOKEN",
     *         in="header",
     *         required=false,
     *         description="CSRF token for session-based auth",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="Referer",
     *         in="header",
     *         required=false,
     *         description="Frontend URL for CSRF protection",
     *         @OA\Schema(type="string", example="http://localhost:3000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of invitations",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="class_id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="teacher_id", type="string", example="571bd78d-4879-44e0-9697-05b6e8bebc5d"),
     *                 @OA\Property(property="student_id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                 @OA\Property(property="email", type="string", example="student2@example.com"),
     *                 @OA\Property(property="invitation_token", type="string", example="ee2df5caeee45080adf83682e6b4c159"),
     *                 @OA\Property(property="status", type="string", example="accepted"),
     *                 @OA\Property(property="expires_at", type="string", example="2025-07-18 09:15:39"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-17T09:15:39.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T09:18:42.000000Z"),
     *                 @OA\Property(
     *                     property="class",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                     @OA\Property(property="name", type="string", example="Biology Class"),
     *                     @OA\Property(property="class_code", type="string", example="zxdfrt"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="student",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                     @OA\Property(property="name", type="string", example="Student User 2"),
     *                     @OA\Property(property="email", type="string", example="student2@example.com")
     *                 ),
     *                 @OA\Property(
     *                     property="teacher",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="571bd78d-4879-44e0-9697-05b6e8bebc5d"),
     *                     @OA\Property(property="name", type="string", example="Teacher User 2")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function getAllInvitations()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/class/invitations/create",
     *     tags={"Invitations"},
     *     summary="Send invitation to student",
     *     description="Teacher or admin sends an invitation to a student to join a class.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="X-XSRF-TOKEN",
     *         in="header",
     *         required=false,
     *         description="CSRF token for session-based auth",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="Referer",
     *         in="header",
     *         required=false,
     *         description="Frontend URL for CSRF protection",
     *         @OA\Schema(type="string", example="http://localhost:3000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_code","email"},
     *             @OA\Property(property="class_code", type="string", example="1234567"),
     *             @OA\Property(property="email", type="string", example="student2@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invitation sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation sent successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="teacher_id", type="string", example="571bd78d-4879-44e0-9697-05b6e8bebc5d"),
     *                 @OA\Property(property="class_id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="student_id", type="string", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *                 @OA\Property(property="email", type="string", example="student2@example.com"),
     *                 @OA\Property(property="invitation_token", type="string", example="ee2df5caeee45080adf83682e6b4c159"),
     *                 @OA\Property(property="expires_at", type="string", example="2025-07-18T09:15:39.099827Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=409, description="Student already enrolled or invitation already sent"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function createInvitation()
    {
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/class/invitations/update/{id}",
     *     tags={"Invitations"},
     *     summary="Update invitation status",
     *     description="Student accepts or declines the invitation.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="X-XSRF-TOKEN",
     *         in="header",
     *         required=false,
     *         description="CSRF token for session-based auth",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="Referer",
     *         in="header",
     *         required=false,
     *         description="Frontend URL for CSRF protection",
     *         @OA\Schema(type="string", example="http://localhost:3000")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invitation ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"accepted","declined"}, example="accepted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation status updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation status updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="class_id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="student_id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="teacher_id", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="email", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="invitation_token", type="string", example="3d565341-2760-4454-bb36-a89cb2ead1a9"),
     *                 @OA\Property(property="status", type="string", example="accepted"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-07-17T09:18:42.000000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-17T09:18:42.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T09:18:42.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid status"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function updateInvitation()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/class/invitations/delete/{id}",
     *     tags={"Invitations"},
     *     summary="Delete invitation",
     *     description="Teacher or admin deletes the invitation before it is accepted or declined.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="X-XSRF-TOKEN",
     *         in="header",
     *         required=false,
     *         description="CSRF token for session-based auth",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="Referer",
     *         in="header",
     *         required=false,
     *         description="Frontend URL for CSRF protection",
     *         @OA\Schema(type="string", example="http://localhost:3000")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invitation ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function deleteInvitation()
    {
    }
}