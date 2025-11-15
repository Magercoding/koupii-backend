<?php

namespace App\Swagger\V1;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Koupii LMS API v1",
 *     description="API documentation for English course LMS - Version 1",
 *     @OA\Contact(
 *         email="support@koupii.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api-koupii.magercoding.com/api/v1",
 *     description="Production server (v1)"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Development server (v1)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Use the authentication token obtained from login"
 * )
 */
class OpenApiV1Spec
{
    // This class is intentionally empty. It only holds OpenAPI v1 annotations.
}