<?php
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\User\UserController;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\V1\User\NotificationController;

Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'profile']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::post('/update', [UserController::class, 'update']); // POST for file upload support
    Route::patch('/update', [UserController::class, 'update']); // PATCH for standard updates
    Route::delete('/destroy', [UserController::class, 'destroy']);
});
Route::patch('/change-password', [UserController::class, 'changePassword'])->middleware('auth:sanctum');

Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
});