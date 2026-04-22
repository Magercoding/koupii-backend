<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Test\TestController;
use App\Http\Controllers\V1\Test\TestSubmissionController;

/**
 * Test System Routes - Main test system with passages and questions
 */

Route::middleware('auth:sanctum')->group(function () {

    // Publicly accessible test listing (for Discover)
    Route::get('tests', [TestController::class, 'index']);
    Route::get('tests/public', [TestController::class, 'index']); // Legacy support

    // Test detail — accessible by all authenticated users (for Discover)
    Route::get('tests/{test}', [TestController::class, 'show']);

    // Test CRUD Routes (Teacher/Admin only: store, update, destroy)
    Route::middleware('role:teacher,admin')->group(function () {
        Route::apiResource('tests', TestController::class)->except(['index', 'show']);
        Route::post('tests/{test}/duplicate', [TestController::class, 'duplicate']);
    });
    
    // Test Taking Routes (Students/Teachers/Admin)
    Route::prefix('tests/{test}')->group(function () {
        Route::get('/attempt', [TestSubmissionController::class, 'attempt']); // Start/continue test
        
        // Generic discover test submissions (currently focusing on reading)
        Route::post('/reading-submission', [\App\Http\Controllers\V1\ReadingTest\ReadingSubmissionController::class, 'start']);
    });
    
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