<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use Dedoc\Scramble\Scramble;
/**
 * @unauthenticated
 */
Route::get('/', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Koupii API is running',
        'timestamp' => now(),
        'version' => '1.0.0',
        'api-documentation' =>  config('app.url') . '/api/documentation',
    ]);
});





Route::domain('docs.example.com')->group(function () {
    Scramble::registerUiRoute('api');
    Scramble::registerJsonSpecificationRoute('api.json');
});

Scramble::registerUiRoute(path: 'docs/v1', api: 'v1');
Scramble::registerJsonSpecificationRoute(path: 'docs/v1.json', api: 'v1');
/**
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local server"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/test",
 *     summary="Test endpoint",
 *     tags={"Test"},
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="API is working!")
 *         )
 *     )
 * )
 */
Route::get('/api/test', function (Request $request) {
    return response()->json(['message' => 'API is working!']);
});
