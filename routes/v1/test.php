<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\TestController;
use App\Http\Controllers\V1\TestSubmissionController;

/**
 * Test System Routes - Main test system with passages and questions
 */

Route::middleware('auth:sanctum')->group(function () {
    
    // Test CRUD Routes (Teacher/Admin)
    Route::middleware('role:teacher,admin')->group(function () {
        Route::apiResource('tests', TestController::class);
        Route::post('tests/{test}/duplicate', [TestController::class, 'duplicate']);
    });
    
    // Test Taking Routes (Students/Teachers/Admin)
    Route::prefix('tests/{test}')->group(function () {
        Route::get('/attempt', [TestSubmissionController::class, 'attempt']); // Start/continue test
        Route::post('/submit', [TestSubmissionController::class, 'submit']);  // Submit answers
        Route::get('/results', [TestSubmissionController::class, 'results']); // Get results
    });
    
    // Public test routes (if test is public)
    Route::get('public/tests', [TestController::class, 'index'])
        ->where(['is_public' => true, 'is_published' => true]);
    
    Route::get('public/tests/{test}', [TestController::class, 'show'])
        ->middleware('check_public_test');
});

/**
 * Admin routes for test management
 */
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/tests')->group(function () {
    Route::get('/', [TestController::class, 'index']); // All tests
    Route::get('/analytics', [TestController::class, 'analytics']); // Test analytics
    Route::patch('/{test}/publish', [TestController::class, 'publish']); // Publish/unpublish
    Route::delete('/bulk', [TestController::class, 'bulkDelete']); // Bulk delete
});