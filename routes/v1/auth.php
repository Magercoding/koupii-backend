<?php
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\RegisterController;
use Illuminate\Support\Facades\Route;


/**
 * Authentication Routes
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

