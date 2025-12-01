<?php
use App\Http\Controllers\V1\Class\ClassController;
use App\Http\Controllers\V1\Class\ClassEnrollmentController;
use App\Http\Controllers\V1\Class\ClassInvitationController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')
    ->prefix('classes')
    ->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::get('/{id}/students', [ClassController::class, 'students']);
        Route::middleware(['role:admin,teacher'])->group(function () {
            Route::post('/create', [ClassController::class, 'store']);
            Route::patch('/update/{id}', [ClassController::class, 'update']);
            Route::delete('/delete/{id}', [ClassController::class, 'destroy']);
        });
    });

Route::middleware('auth:sanctum')
    ->prefix('enrollments')
    ->group(function () {
        Route::get('/', [ClassEnrollmentController::class, 'index']);
        Route::get('/{id}', [ClassEnrollmentController::class, 'show']);
        Route::post('/create', [ClassEnrollmentController::class, 'store'])->middleware(['role:student']);
        Route::middleware(['role:admin,teacher'])->group(function () {
            Route::patch('/update/{id}', [ClassEnrollmentController::class, 'update']);
            Route::delete('/delete/{id}', [ClassEnrollmentController::class, 'destroy']);
        });
    });

Route::middleware('auth:sanctum')
    ->prefix('invitations')
    ->group(function () {
        Route::get('/', [ClassInvitationController::class, 'index']);
        Route::patch('/update/{id}', [ClassInvitationController::class, 'update'])->middleware('role:student');
        Route::middleware(['role:admin,teacher'])->group(function () {
            Route::post('/create', [ClassInvitationController::class, 'store'])->middleware('role:admin,teacher');
            Route::delete('/delete/{id}', [ClassInvitationController::class, 'destroy'])->middleware('role:admin,teacher');
        });
    });
