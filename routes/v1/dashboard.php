<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Dashboard\TeacherDashboardController;

Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('dashboard')->group(function () {
    Route::get('/statistics', [TeacherDashboardController::class, 'statistics']);
    Route::get('/statistics/reading', [TeacherDashboardController::class, 'readingStatistics']);
    Route::get('/statistics/writing', [TeacherDashboardController::class, 'writingStatistics']);
    Route::get('/statistics/listening', [TeacherDashboardController::class, 'listeningStatistics']);
});
