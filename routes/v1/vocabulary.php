<?php

use App\Http\Controllers\V1\Vocabulary\VocabularyController;
use App\Http\Controllers\V1\VocabularyCategory\VocabularyCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('vocab')
    ->group(function () {
        Route::middleware('role:admin,teacher')->prefix('categories')->group(function () {
            Route::get('/', [VocabularyCategoryController::class, 'index']);
            Route::get('/{id}', [VocabularyCategoryController::class, 'show']);
            Route::post('/create', [VocabularyCategoryController::class, 'store']);
            Route::patch('/update/{id}', [VocabularyCategoryController::class, 'update']);
            Route::delete('/delete/{id}', [VocabularyCategoryController::class, 'destroy']);
        });

        Route::middleware('role:admin,teacher,student')->group(function () {
            Route::get('/vocabularies', [VocabularyController::class, 'index']);
            Route::post('/{id}/bookmark', [VocabularyController::class, 'toggleBookmark']);
        });

        Route::middleware('role:admin,teacher')->group(function () {
            Route::get('/{id}', [VocabularyController::class, 'show']);
            Route::post('/create', [VocabularyController::class, 'store']);
            Route::patch('/update/{id}', [VocabularyController::class, 'update']);
            Route::delete('/delete/{id}', [VocabularyController::class, 'destroy']);
        });
    });