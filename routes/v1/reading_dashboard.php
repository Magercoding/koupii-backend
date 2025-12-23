<?php

use App\Http\Controllers\V1\ReadingTest\{
    ReadingDashboardController,
    ReadingReviewController,
    ReadingTaskAssignmentController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reading Task Management API Routes
|--------------------------------------------------------------------------
|
| Routes for reading task reviews, assignments, and dashboard
| Organized to complement the main reading_test.php and reading_task.php routes
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // === READING DASHBOARD ===
    Route::prefix('reading/dashboard')->name('reading.dashboard.')->group(function () {
        Route::get('/student', [ReadingDashboardController::class, 'student'])->name('student');
        Route::get('/teacher', [ReadingDashboardController::class, 'teacher'])
            ->middleware('role:admin,teacher')->name('teacher');
        Route::get('/admin', [ReadingDashboardController::class, 'admin'])
            ->middleware('role:admin')->name('admin');
    });
    
    // === READING REVIEWS ===
    Route::prefix('reading/reviews')->name('reading.reviews.')->group(function () {
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/', [ReadingReviewController::class, 'index'])->name('index');
            Route::post('/', [ReadingReviewController::class, 'store'])->name('store');
            Route::get('/{id}', [ReadingReviewController::class, 'show'])->name('show');
            Route::put('/{id}', [ReadingReviewController::class, 'update'])->name('update');
            Route::delete('/{id}', [ReadingReviewController::class, 'destroy'])->name('destroy');
            
            // Review Operations
            Route::post('/submission/{submissionId}', [ReadingReviewController::class, 'review'])->name('create');
            Route::patch('/{id}/approve', [ReadingReviewController::class, 'approve'])->name('approve');
            Route::patch('/{id}/reject', [ReadingReviewController::class, 'reject'])->name('reject');
            Route::post('/{id}/add-comment', [ReadingReviewController::class, 'addComment'])->name('add-comment');
        });
        
        // Teacher/Admin Review Management
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/pending', [ReadingReviewController::class, 'getPendingReviews'])->name('pending');
            Route::post('/bulk', [ReadingReviewController::class, 'bulkReview'])->name('bulk');
            Route::get('/statistics', [ReadingReviewController::class, 'getReviewStatistics'])->name('statistics');
        });
    });

    // === READING TASK ASSIGNMENTS ===
    Route::prefix('reading/assignments')->name('reading.assignments.')->group(function () {
        Route::get('/', [ReadingTaskAssignmentController::class, 'index'])->name('index');
        Route::post('/', [ReadingTaskAssignmentController::class, 'store'])
            ->middleware('role:admin,teacher')->name('store');
        Route::get('/{id}', [ReadingTaskAssignmentController::class, 'show'])->name('show');
        Route::put('/{id}', [ReadingTaskAssignmentController::class, 'update'])
            ->middleware('role:admin,teacher')->name('update');
        Route::delete('/{id}', [ReadingTaskAssignmentController::class, 'destroy'])
            ->middleware('role:admin,teacher')->name('destroy');
            
        // Assignment Management
        Route::post('/{id}/assign-to-class', [ReadingTaskAssignmentController::class, 'assignToClass'])
            ->middleware('role:admin,teacher')->name('assign-class');
        Route::post('/{id}/assign-to-student', [ReadingTaskAssignmentController::class, 'assignToStudent'])
            ->middleware('role:admin,teacher')->name('assign-student');
        Route::patch('/{id}/extend-deadline', [ReadingTaskAssignmentController::class, 'extendDeadline'])
            ->middleware('role:admin,teacher')->name('extend-deadline');
        Route::patch('/{id}/publish', [ReadingTaskAssignmentController::class, 'publish'])
            ->middleware('role:admin,teacher')->name('publish');
        Route::patch('/{id}/unpublish', [ReadingTaskAssignmentController::class, 'unpublish'])
            ->middleware('role:admin,teacher')->name('unpublish');
            
        // Bulk Operations
        Route::post('/bulk/assign', [ReadingTaskAssignmentController::class, 'bulkAssign'])
            ->middleware('role:admin,teacher')->name('bulk-assign');
        Route::patch('/bulk/update-deadline', [ReadingTaskAssignmentController::class, 'bulkUpdateDeadline'])
            ->middleware('role:admin,teacher')->name('bulk-deadline');
        Route::delete('/bulk/delete', [ReadingTaskAssignmentController::class, 'bulkDelete'])
            ->middleware('role:admin,teacher')->name('bulk-delete');
    });
    
    // === READING ANALYTICS ===
    Route::prefix('reading/analytics')->name('reading.analytics.')->group(function () {
        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/class/{classId}', [ReadingDashboardController::class, 'getClassAnalytics'])->name('class');
            Route::get('/student/{studentId}', [ReadingDashboardController::class, 'getStudentAnalytics'])->name('student');
            Route::get('/comprehension-report', [ReadingDashboardController::class, 'getComprehensionReport'])->name('comprehension');
        });
    });
});