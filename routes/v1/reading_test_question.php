<?php
use App\Http\Controllers\V1\ReadingTest\ReadingTestQuestionController;
use Illuminate\Support\Facades\Route;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('reading')
        ->group(function () {
            Route::get('/tests', [ReadingTestQuestionController::class, 'index']);
            Route::get('/tests/{id}', [ReadingTestQuestionController::class, 'show']);
            Route::middleware(['role:admin,teacher'])->group(function () {
                Route::post('/create', [ReadingTestQuestionController::class, 'store']);
                Route::patch('/update/{id}', [ReadingTestQuestionController::class, 'update']);
                Route::delete('/delete/passage/{passageId}', [ReadingTestQuestionController::class, 'deletePassage']);
                Route::delete('/delete/question/{questionId}', [ReadingTestQuestionController::class, 'deleteQuestion']);
                Route::delete('/delete/{id}', [ReadingTestQuestionController::class, 'destroy']);
            });
        });
});