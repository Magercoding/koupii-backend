<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\StudentDashboard\StudentDashboardController;
use App\Http\Controllers\V1\Assignment\AssignmentController;

/*
|--------------------------------------------------------------------------
| Student Dashboard & Assignment API Routes
|--------------------------------------------------------------------------
|
| Routes for student dashboard and assignment management
|
*/

// Student Dashboard Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Student Dashboard
    Route::prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'dashboard'])
            ->name('dashboard');
        
        Route::get('/assignments/{assignmentId}/{type}/details', [StudentDashboardController::class, 'getAssignmentDetails'])
            ->name('assignment.details');
        
        Route::post('/assignments/{assignmentId}/{type}/start', [StudentDashboardController::class, 'startAssignment'])
            ->name('assignment.start');
        
        Route::post('/assignments/{assignmentId}/{type}/submit', [StudentDashboardController::class, 'submitAssignment'])
            ->name('assignment.submit');
    });

    // Teacher Assignment Management
    Route::prefix('assignments')->name('assignments.')->group(function () {
        // Create assignment (assign task to class)
        Route::post('/assign', [AssignmentController::class, 'assignTask'])
            ->name('assign');
        
        // Get assignments for a class
        Route::get('/class/{classId}', [AssignmentController::class, 'getClassAssignments'])
            ->name('class.index');
        
        // Get assignment statistics
        Route::get('/{assignmentId}/{type}/stats', [AssignmentController::class, 'getAssignmentStats'])
            ->name('stats');
        
        // Update assignment
        Route::put('/{assignmentId}/{type}', [AssignmentController::class, 'updateAssignment'])
            ->name('update');
        
        // Delete assignment
        Route::delete('/{assignmentId}/{type}', [AssignmentController::class, 'deleteAssignment'])
            ->name('delete');
    });
});