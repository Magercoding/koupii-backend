<?php

use App\Http\Controllers\V1\ListeningTest\ListeningTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Listening Test API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Listening Test module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "v1" prefix.
|
*/

Route::prefix('listening-tests')->name('listening-tests.')->group(function () {
    Route::get('/', [ListeningTestController::class, 'index'])->name('index');
    Route::post('/', [ListeningTestController::class, 'store'])->name('store');
    Route::get('/search', [ListeningTestController::class, 'search'])->name('search');
    Route::get('/{id}', [ListeningTestController::class, 'show'])->name('show');
    Route::put('/{id}', [ListeningTestController::class, 'update'])->name('update');
    Route::delete('/{id}', [ListeningTestController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/toggle-publish', [ListeningTestController::class, 'togglePublish'])->name('toggle-publish');
});