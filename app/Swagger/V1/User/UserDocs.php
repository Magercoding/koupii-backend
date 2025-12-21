<?php


namespace App\Swagger\V1\User;

use OpenApi\Annotations as OA;

class UserDocs
{
    /**
     * @OA\Get(
     *     path="/api/v1/profile",
     *     tags={"Profile"},
     *     summary="Get current user profile",
     *     description="Retrieve profile information for the authenticated user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Current user profile data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid", example="d2fb93ec-6043-4384-814d-0e48f36aed50"),
     *             @OA\Property(property="name", type="string", example="Fika"),
     *             @OA\Property(property="email", type="string", example="student2@example.com"),
     *             @OA\Property(property="role", type="string", example="student"),
     *             @OA\Property(property="avatar", type="string", nullable=true, example="https://api-koupii.magercoding.com/storage/avatar/6887cfd4a9ec8.png"),
     *             @OA\Property(property="bio", type="string", example="Student from Informatics")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function getProfile() {}

    /**
     * @OA\Get(
     *     path="/api/v1/profile/{id}",
     *     tags={"Profile"},
     *     summary="Get user details by ID",
     *     description="Retrieve public details of a user by their ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID (UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Teacher 1"),
     *             @OA\Property(property="email", type="string", example="teacher1@example.com"),
     *             @OA\Property(property="role", type="string", example="teacher"),
     *             @OA\Property(property="avatar", type="string", nullable=true, example="http://localhost:8000/storage/avatar/68da7f36cac36.JPG"),
     *             @OA\Property(property="bio", type="string", example="Lorem ipsum dolor sit amet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function getUserDetail() {}

    /**
     * @OA\Post(
     *     path="/api/v1/profile/update",
     *     tags={"Profile"},
     *     summary="Update user profile",
     *     description="Update authenticated user's profile including name, email, role, avatar, and bio.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="_method",
     *         in="query",
     *         required=true,
     *         description="Override HTTP method for PATCH requests",
     *         @OA\Schema(type="string", example="PATCH")
     *     ),
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "role"},
     *                 @OA\Property(property="name", type="string", example="Fika"),
     *                 @OA\Property(property="email", type="string", format="email", example="student2@example.com"),
     *                 @OA\Property(property="role", type="string", enum={"student", "teacher", "admin"}, example="student"),
     *                 @OA\Property(property="bio", type="string", example="Lorem ipsum dolor sit amet"),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="User avatar image file"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function updateProfile() {}

    /**
     * @OA\Delete(
     *     path="/api/v1/profile/destroy",
     *     tags={"Profile"},
     *     summary="Delete user profile",
     *     description="Delete the authenticated user's account, including avatar file if present.",
     *     security={{"bearerAuth":{}}},
     *  @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function deleteProfile() {}

    /**
     * @OA\Patch(
     *     path="/api/v1/change-password",
     *     tags={"User"},
     *     summary="Change user password",
     *     description="Change the authenticated user's password.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token. Example: Bearer {access_token}",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *             @OA\Property(property="access_token", type="string", example="1|abcdef1234567890"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Current password is incorrect"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function changePassword() {}
}

