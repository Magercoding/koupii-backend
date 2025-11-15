<?php

namespace App\Swagger\V1\User;

use OpenApi\Annotations as OA;
/**
 * @OA\Patch(
 *     path="/api/v1/user/password/change-password",
 *     tags={"Password"},
 *     summary="Change user password",
 *     description="Change the authenticated user's password. Requires the current password and a new one. CSRF protection via XSRF-TOKEN and Referer header.",
 *     operationId="changeUserPassword",
 *     security={{"sanctum":{}}},
 *
 *     @OA\Parameter(
 *         name="X-XSRF-TOKEN",
 *         in="header",
 *         required=false,
 *         description="CSRF token for session-based auth (Sanctum)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="Referer",
 *         in="header",
 *         required=false,
 *         description="Referring URL Frontend for CSRF protection",
 *         @OA\Schema(type="string", example="http://localhost:3000")
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"current_password", "new_password", "new_password_confirmation"},
 *             @OA\Property(property="current_password", type="string", example="Password123!"),
 *             @OA\Property(property="new_password", type="string", format="password", example="Passwordkece123!"),
 *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="Passwordkece123!")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Password changed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password changed successfully.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={
 *                     "current_password": {"Current password is incorrect"},
 *                     "new_password": {"The new password must be at least 8 characters."}
 *                 }
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated or current password incorrect",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     )
 * )
 */
class ChangePasswordDocs
{
}
