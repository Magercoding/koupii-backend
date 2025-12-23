<?php


namespace App\Swagger\V1\Auth;

use OpenApi\Annotations as OA;

class OauthDocs {
    /**
     * @OA\Get(
     *     path="/api/v1/oauth/google/redirect",
     *     tags={"OAuth"},
     *     summary="Redirect to Google OAuth",
     *     description="Redirects the user to Google's OAuth consent screen",
     *     @OA\Response(
     *         response=302,
     *         description="Redirection to Google OAuth"
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function redirectToGoogle() {}

    /**
     * @OA\Get(
     *     path="/api/v1/oauth/google/callback",
     *     tags={"OAuth"},
     *     summary="Handle Google OAuth Callback",
     *     description="Handles the callback from Google OAuth and authenticates the user",
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="access_token", type="string", example="1|abcdef1234567890"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Authentication failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function handleGoogleCallback() {}


    /**
     * @OA\Get(
     *     path="/api/v1/oauth/facebook/redirect",
     *     tags={"OAuth"},
     *     summary="Redirect to Facebook OAuth",
     *     description="Redirects the user to Facebook's OAuth consent screen",
     *     @OA\Response(
     *         response=302,
     *         description="Redirection to Facebook OAuth"
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function redirectToFacebook() {}

    /**
     * @OA\Get(
     *     path="/api/v1/oauth/facebook/callback",
     *     tags={"OAuth"},
     *     summary="Handle Facebook OAuth Callback",
     *     description="Handles the callback from Facebook OAuth and authenticates the user",
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="access_token", type="string", example="1|abcdef1234567890"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Authentication failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function handleFacebookCallback() {}
}

