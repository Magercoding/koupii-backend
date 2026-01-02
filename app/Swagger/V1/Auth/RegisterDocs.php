<?php

namespace App\Swagger\V1\Auth;

use OpenApi\Annotations as OA;
/**
 * @OA\Post(
 *     path="/api/v1/auth/register",
 *     tags={"Auth"},
 *     summary="Register a new user",
 *     description="Registers a user and returns an access token",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"name", "email", "password", "role"},
 *                 @OA\Property(property="name", type="string", example="Fika Riyadi"),
 *                 @OA\Property(property="email", type="string", format="email", example="fika@example.com"),
 *                 @OA\Property(property="password", type="string", format="password", example="secret123"),
 *                 @OA\Property(property="role", type="string", example="user"),
 *                 @OA\Property(
 *                     property="profile_picture",
 *                     type="string",
 *                     format="binary",
 *                     description="Upload profile picture (jpg, png, gif)"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Registered successfully"),
 *             @OA\Property(property="token", type="string", example="1|abcdef1234567890")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation failed"),
 *     @OA\Response(response=500, description="Internal server error")
 * )
 */
class RegisterDocs
{
}

