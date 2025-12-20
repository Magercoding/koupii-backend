<?php

use App\Http\Controllers\V1\WritingTest\WritingTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Writing Test API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Writing Test module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "v1" prefix.
|
*/


    Route::prefix('writing-tests')->name('writing-tests.')->group(function () {
        Route::get('/', [WritingTestController::class, 'index'])->name('index');
        Route::post('/', [WritingTestController::class, 'store'])->name('store');
        Route::get('/search', [WritingTestController::class, 'search'])->name('search');
        Route::get('/{id}', [WritingTestController::class, 'show'])->name('show');
        Route::put('/{id}', [WritingTestController::class, 'update'])->name('update');
        Route::delete('/{id}', [WritingTestController::class, 'destroy'])->name('destroy');
    });
