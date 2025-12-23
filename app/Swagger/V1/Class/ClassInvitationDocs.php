<?php

namespace App\Swagger\V1\Class;

use OpenApi\Annotations as OA;

class ClassInvitationDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/invitations",
     *     tags={"Invitations"},
     *     summary="Get all invitations based on user role",
     *     description="Admin sees all invitations, teacher sees only invitations in their classes, student sees only invitations sent to them.",
     *     security={{"bearerAuth":{}}},
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
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="expires_at", type="string", example="2025-07-18 09:15:39"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-17T09:15:39.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-07-17T09:18:42.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function getAllInvitations() {}

    /**
     * @OA\Post(
     *     path="/api/v1/invitations/create",
     *     tags={"Invitations"},
     *     summary="Send invitation to student",
     *     description="Teacher or admin sends an invitation to a student to join a class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_code","email"},
     *             @OA\Property(property="class_code", type="string", example="ABCD12345"),
     *             @OA\Property(property="email", type="string", example="student@example.com")
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
     *                 @OA\Property(property="email", type="string", example="student@example.com"),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=409, description="Student already enrolled or invitation already sent"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function createInvitation() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/invitations/accept/{id}",
     *     tags={"Invitations"},
     *     summary="Accept invitation",
     *     description="Student accepts the invitation to join a class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invitation ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation accepted successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="status", type="string", example="accepted")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - You can only accept your own invitations"),
     *     @OA\Response(response=404, description="Invitation not found")
     * )
     */
     public function acceptInvitation() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/invitations/decline/{id}",
     *     tags={"Invitations"},
     *     summary="Decline invitation",
     *     description="Student declines the invitation to join a class.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invitation ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation declined successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation declined successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="status", type="string", example="declined")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - You can only decline your own invitations"),
     *     @OA\Response(response=404, description="Invitation not found")
     * )
     */
    public function declineInvitation() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/invitations/update/{id}",
     *     tags={"Invitations"},
     *     summary="Update invitation details",
     *     description="Teacher or admin updates invitation details (e.g., change email if wrong person was invited).",
     *     security={{"bearerAuth":{}}},
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
     *             required={"class_code","email"},
     *             @OA\Property(property="class_code", type="string", example="ABCD12345"),
     *             @OA\Property(property="email", type="string", example="newemail@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="caccde25-0463-4acf-979e-46fca9b2315a"),
     *                 @OA\Property(property="email", type="string", example="newemail@example.com"),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - You can only update invitations for your own classes"),
     *     @OA\Response(response=404, description="Invitation not found"),
     *     @OA\Response(response=409, description="Student already enrolled or invitation already sent")
     * )
     */
    public function updateInvitation() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/invitations/delete/{id}",
     *     tags={"Invitations"},
     *     summary="Delete invitation",
     *     description="Teacher or admin deletes the invitation before it is accepted or declined.",
     *     security={{"bearerAuth":{}}},
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
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Invitation not found")
     * )
     */
    public function deleteInvitation() {}
}