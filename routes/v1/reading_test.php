<?php

use App\Http\Controllers\V1\ReadingTest\ReadingTestQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reading Test API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Reading Test module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "v1" prefix.
|
*/

Route::prefix('reading-tests')->name('reading-tests.')->group(function () {
    Route::get('/', [ReadingTestQuestionController::class, 'index'])->name('index');
    Route::post('/', [ReadingTestQuestionController::class, 'store'])->name('store');
    Route::get('/search', [ReadingTestQuestionController::class, 'search'])->name('search');
    Route::get('/{id}', [ReadingTestQuestionController::class, 'show'])->name('show');
    Route::put('/{id}', [ReadingTestQuestionController::class, 'update'])->name('update');
    Route::delete('/{id}', [ReadingTestQuestionController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/toggle-publish', [ReadingTestQuestionController::class, 'togglePublish'])->name('toggle-publish');
    
    // Specific deletion routes
    Route::delete('/passages/{passageId}', [ReadingTestQuestionController::class, 'deletePassage'])->name('delete-passage');
    Route::delete('/questions/{questionId}', [ReadingTestQuestionController::class, 'deleteQuestion'])->name('delete-question');
});