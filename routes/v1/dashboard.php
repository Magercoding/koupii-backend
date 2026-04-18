<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Dashboard\TeacherDashboardController;

Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('dashboard')->group(function () {
    Route::get('/statistics', [TeacherDashboardController::class, 'statistics']);
    Route::get('/statistics/reading', [TeacherDashboardController::class, 'readingStatistics']);
    Route::get('/statistics/writing', [TeacherDashboardController::class, 'writingStatistics']);
    Route::get('/statistics/listening', [TeacherDashboardController::class, 'listeningStatistics']);
    Route::get('/statistics/speaking', [TeacherDashboardController::class, 'speakingStatistics']);
});

use App\Http\Controllers\V1\Admin\AdminDashboardController;
use App\Http\Controllers\V1\Admin\AdminUserController;
use App\Http\Controllers\V1\Admin\AdminClassController;
use App\Http\Controllers\V1\Admin\AdminPlanController;
use App\Http\Controllers\V1\Admin\AdminSubscriptionController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/overview', [AdminDashboardController::class, 'getOverview'])->name('admin.overview');
    
    // User Management API
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.updateRole');

    // Class Management API
    Route::get('/classes', [AdminClassController::class, 'index'])->name('admin.classes.index');
    Route::delete('/classes/{id}', [AdminClassController::class, 'destroy'])->name('admin.classes.destroy');

    // Plans Management API
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('admin.plans.store');
    Route::put('/plans/{id}', [AdminPlanController::class, 'update'])->name('admin.plans.update');
    Route::delete('/plans/{id}', [AdminPlanController::class, 'destroy'])->name('admin.plans.destroy');

    // Subscriptions API
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    Route::get('/subscriptions/stats', [AdminSubscriptionController::class, 'stats'])->name('admin.subscriptions.stats');
});
