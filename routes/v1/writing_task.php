<?php

use App\Http\Controllers\V1\WritingTask\WritingTaskController;
use App\Http\Controllers\V1\WritingTask\WritingTaskAssignmentController;
use App\Http\Controllers\V1\WritingTask\WritingSubmissionController;
use App\Http\Controllers\V1\WritingTask\WritingReviewController;
use App\Http\Controllers\V1\WritingTask\WritingDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Writing Task API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Writing Task module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "v1" prefix.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Core Writing Task CRUD Routes
    Route::prefix('writing-tasks')->name('writing-tasks.')->group(function () {
        Route::get('/', [WritingTaskController::class, 'index'])->name('index');
        Route::post('/', [WritingTaskController::class, 'store'])->name('store');
        Route::get('/{id}', [WritingTaskController::class, 'show'])->name('show');
        Route::put('/{id}', [WritingTaskController::class, 'update'])->name('update');
        Route::delete('/{id}', [WritingTaskController::class, 'destroy'])->name('destroy');

        // Task Assignment Routes
        Route::prefix('{id}/assignments')->name('assignments.')->group(function () {
            Route::get('/', [WritingTaskAssignmentController::class, 'getAssignments'])->name('index');
            Route::post('/', [WritingTaskAssignmentController::class, 'assignToClassrooms'])->name('assign');
            Route::delete('/{classroomId}', [WritingTaskAssignmentController::class, 'removeFromClassroom'])->name('remove');
        });

        // Submission Routes
        Route::prefix('{taskId}/submissions')->name('submissions.')->group(function () {
            Route::get('/', [WritingSubmissionController::class, 'index'])->name('index');
            Route::post('/', [WritingSubmissionController::class, 'submit'])->name('submit');
            Route::post('/draft', [WritingSubmissionController::class, 'saveDraft'])->name('save-draft');
            Route::post('/retake', [WritingSubmissionController::class, 'createRetake'])->name('create-retake');
            Route::get('/{submissionId}', [WritingSubmissionController::class, 'show'])->name('show');
            Route::patch('/{submissionId}/done', [WritingSubmissionController::class, 'markAsDone'])->name('mark-done');

            // Review Routes (nested under submissions)
            Route::prefix('{submissionId}/review')->name('review.')->group(function () {
                Route::post('/', [WritingReviewController::class, 'review'])->name('create');
            });
        });
    });

    // Bulk Operations Routes
    Route::prefix('writing-tasks-bulk')->name('writing-tasks.bulk.')->group(function () {
        Route::post('/assign', [WritingTaskAssignmentController::class, 'bulkAssign'])->name('assign');
        Route::delete('/delete', [WritingTaskController::class, 'bulkDelete'])->name('delete');
        Route::patch('/publish', [WritingTaskController::class, 'bulkPublish'])->name('publish');
    });

    // Review Management Routes
    Route::prefix('writing-reviews')->name('writing-reviews.')->group(function () {
        Route::get('/pending', [WritingReviewController::class, 'getPendingReviews'])->name('pending');
        Route::post('/bulk', [WritingReviewController::class, 'bulkReview'])->name('bulk');
        Route::get('/statistics', [WritingReviewController::class, 'getReviewStatistics'])->name('statistics');
    });

    // Dashboard Routes
    Route::prefix('writing-dashboard')->name('writing-dashboard.')->group(function () {
        Route::get('/student', [WritingDashboardController::class, 'student'])->name('student');
        Route::get('/teacher', [WritingDashboardController::class, 'teacher'])->name('teacher');
        Route::get('/admin', [WritingDashboardController::class, 'admin'])->name('admin');
    });

    // Advanced Task Management Routes
    Route::prefix('writing-task-management')->name('writing-task-management.')->group(function () {
        Route::post('/{id}/duplicate', [WritingTaskController::class, 'duplicate'])->name('duplicate');
        Route::patch('/{id}/toggle-publish', [WritingTaskController::class, 'togglePublish'])->name('toggle-publish');
        Route::post('/{id}/archive', [WritingTaskController::class, 'archive'])->name('archive');
        Route::post('/{id}/restore', [WritingTaskController::class, 'restore'])->name('restore');
        Route::get('/{id}/deletion-impact', [WritingTaskController::class, 'getDeletionImpact'])->name('deletion-impact');
        Route::delete('/{id}/force', [WritingTaskController::class, 'forceDestroy'])->name('force-destroy');
    });

    // Task Analytics Routes
    Route::prefix('writing-task-analytics')->name('writing-task-analytics.')->group(function () {
        Route::get('/{id}/statistics', [WritingTaskController::class, 'getTaskStatistics'])->name('statistics');
        Route::get('/{id}/progress', [WritingTaskController::class, 'getTaskProgress'])->name('progress');
        Route::get('/{id}/submissions-summary', [WritingTaskController::class, 'getSubmissionsSummary'])->name('submissions-summary');
        Route::get('/teacher/{teacherId}/overview', [WritingTaskController::class, 'getTeacherOverview'])->name('teacher-overview');
    });

    // File Management Routes
    Route::prefix('writing-task-files')->name('writing-task-files.')->group(function () {
        Route::post('/upload', [WritingTaskController::class, 'uploadFile'])->name('upload');
        Route::delete('/{fileId}', [WritingTaskController::class, 'deleteFile'])->name('delete');
        Route::get('/{fileId}/download', [WritingTaskController::class, 'downloadFile'])->name('download');
    });

    // Export Routes
    Route::prefix('writing-task-export')->name('writing-task-export.')->group(function () {
        Route::get('/{id}/submissions', [WritingTaskController::class, 'exportSubmissions'])->name('submissions');
        Route::get('/{id}/grades', [WritingTaskController::class, 'exportGrades'])->name('grades');
        Route::get('/teacher/{teacherId}/report', [WritingTaskController::class, 'exportTeacherReport'])->name('teacher-report');
    });
});