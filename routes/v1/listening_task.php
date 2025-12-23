<?php

use App\Http\Controllers\V1\Listening\{
    ListeningReviewController,
    ListeningTaskAssignmentController,
    ListeningDashboardController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Listening Task Management API Routes
|--------------------------------------------------------------------------
|
| Routes for listening task reviews, assignments, and dashboard
| Organized to complement the main listening.php routes
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // === LISTENING TASK ASSIGNMENTS ===
    Route::prefix('listening/assignments')->name('listening.assignments.')->group(function () {
        Route::get('/', [ListeningTaskAssignmentController::class, 'index'])->name('index');
        Route::post('/', [ListeningTaskAssignmentController::class, 'store'])
            ->middleware('role:admin,teacher')->name('store');
        Route::get('/{id}', [ListeningTaskAssignmentController::class, 'show'])->name('show');
        Route::put('/{id}', [ListeningTaskAssignmentController::class, 'update'])
            ->middleware('role:admin,teacher')->name('update');
        Route::delete('/{id}', [ListeningTaskAssignmentController::class, 'destroy'])
            ->middleware('role:admin,teacher')->name('destroy');
            
        // Assignment Management
        Route::post('/{id}/assign-to-class', [ListeningTaskAssignmentController::class, 'assignToClass'])
            ->middleware('role:admin,teacher')->name('assign-class');
        Route::post('/{id}/assign-to-student', [ListeningTaskAssignmentController::class, 'assignToStudent'])
            ->middleware('role:admin,teacher')->name('assign-student');
        Route::patch('/{id}/extend-deadline', [ListeningTaskAssignmentController::class, 'extendDeadline'])
            ->middleware('role:admin,teacher')->name('extend-deadline');
        Route::patch('/{id}/publish', [ListeningTaskAssignmentController::class, 'publish'])
            ->middleware('role:admin,teacher')->name('publish');
        Route::patch('/{id}/unpublish', [ListeningTaskAssignmentController::class, 'unpublish'])
            ->middleware('role:admin,teacher')->name('unpublish');
            
        // Bulk Operations
        Route::post('/bulk/assign', [ListeningTaskAssignmentController::class, 'bulkAssign'])
            ->middleware('role:admin,teacher')->name('bulk-assign');
        Route::patch('/bulk/update-deadline', [ListeningTaskAssignmentController::class, 'bulkUpdateDeadline'])
            ->middleware('role:admin,teacher')->name('bulk-deadline');
        Route::delete('/bulk/delete', [ListeningTaskAssignmentController::class, 'bulkDelete'])
            ->middleware('role:admin,teacher')->name('bulk-delete');
    });

    // === LISTENING REVIEWS ===
    Route::prefix('listening/reviews')->name('listening.reviews.')->group(function () {
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/', [ListeningReviewController::class, 'index'])->name('index');
            Route::post('/', [ListeningReviewController::class, 'store'])->name('store');
            Route::get('/{id}', [ListeningReviewController::class, 'show'])->name('show');
            Route::put('/{id}', [ListeningReviewController::class, 'update'])->name('update');
            Route::delete('/{id}', [ListeningReviewController::class, 'destroy'])->name('destroy');
            
            // Review Operations
            Route::post('/submission/{submissionId}', [ListeningReviewController::class, 'review'])->name('create');
            Route::patch('/{id}/approve', [ListeningReviewController::class, 'approve'])->name('approve');
            Route::patch('/{id}/reject', [ListeningReviewController::class, 'reject'])->name('reject');
            Route::post('/{id}/add-comment', [ListeningReviewController::class, 'addComment'])->name('add-comment');
        });
        
        // Teacher/Admin Review Management
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/pending', [ListeningReviewController::class, 'getPendingReviews'])->name('pending');
            Route::post('/bulk', [ListeningReviewController::class, 'bulkReview'])->name('bulk');
            Route::get('/statistics', [ListeningReviewController::class, 'getReviewStatistics'])->name('statistics');
        });
    });

    // === LISTENING DASHBOARD ===
    Route::prefix('listening/dashboard')->name('listening.dashboard.')->group(function () {
        Route::get('/student', [ListeningDashboardController::class, 'student'])->name('student');
        Route::get('/teacher', [ListeningDashboardController::class, 'teacher'])
            ->middleware('role:admin,teacher')->name('teacher');
        Route::get('/admin', [ListeningDashboardController::class, 'admin'])
            ->middleware('role:admin')->name('admin');
    });
    
    // === LISTENING ANALYTICS ===
    Route::prefix('listening/analytics')->name('listening.analytics.')->group(function () {
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/class/{classId}', [ListeningDashboardController::class, 'getClassAnalytics'])->name('class');
            Route::get('/student/{studentId}', [ListeningDashboardController::class, 'getStudentAnalytics'])->name('student');
            Route::get('/comprehension-report', [ListeningDashboardController::class, 'getComprehensionReport'])->name('comprehension');
        });
    });
});