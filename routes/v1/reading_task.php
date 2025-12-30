<?php

use App\Http\Controllers\V1\ReadingTask\ReadingTaskController;
use App\Http\Controllers\V1\ReadingTask\ReadingTaskAssignmentController;
use App\Http\Controllers\V1\ReadingTest\ReadingSubmissionController;
use App\Http\Controllers\V1\ReadingTest\ReadingAnswerController;
use App\Http\Controllers\V1\ReadingTest\ReadingVocabularyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reading Task API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Reading Task module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group and "v1" prefix.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Core Reading Task CRUD Routes
    Route::prefix('reading-tasks')->name('reading-tasks.')->group(function () {
        Route::get('/', [ReadingTaskController::class, 'index'])->name('index');
        Route::post('/', [ReadingTaskController::class, 'store'])->name('store');
        Route::get('/{id}', [ReadingTaskController::class, 'show'])->name('show');
        Route::put('/{id}', [ReadingTaskController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReadingTaskController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-publish', [ReadingTaskController::class, 'togglePublish'])->name('toggle-publish');
        
        // Task Assignment Routes  
        Route::prefix('{id}/assignments')->name('assignments.')->group(function () {
            Route::get('/', [ReadingTaskAssignmentController::class, 'getAssignments'])->name('index');
            Route::post('/', [ReadingTaskAssignmentController::class, 'assignToClassrooms'])->name('assign');
            Route::delete('/{classroomId}', [ReadingTaskAssignmentController::class, 'removeFromClassroom'])->name('remove');
        });
        
        // Submission Routes
        Route::prefix('{taskId}/submissions')->name('submissions.')->group(function () {
            Route::get('/', [ReadingSubmissionController::class, 'index'])->name('index');
            Route::post('/', [ReadingSubmissionController::class, 'submit'])->name('submit');
            Route::get('/{submissionId}', [ReadingSubmissionController::class, 'show'])->name('show');
            Route::patch('/{submissionId}/done', [ReadingSubmissionController::class, 'markAsDone'])->name('mark-done');
        });
    });

    // Reading Submissions Routes
    Route::prefix('reading/submissions')->name('reading.submissions.')->group(function () {
        Route::get('/', [ReadingSubmissionController::class, 'index'])->name('index');
        Route::post('/', [ReadingSubmissionController::class, 'store'])->name('store');
        Route::get('/{id}', [ReadingSubmissionController::class, 'show'])->name('show');
        Route::put('/{id}', [ReadingSubmissionController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReadingSubmissionController::class, 'destroy'])->name('destroy');
    });

    // Reading Answers Routes
    Route::prefix('reading/answers')->name('reading.answers.')->group(function () {
        Route::get('/', [ReadingAnswerController::class, 'index'])->name('index');
        Route::post('/', [ReadingAnswerController::class, 'store'])->name('store');
        Route::get('/{id}', [ReadingAnswerController::class, 'show'])->name('show');
        Route::put('/{id}', [ReadingAnswerController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReadingAnswerController::class, 'destroy'])->name('destroy');
    });

    // Reading Vocabulary Routes
    Route::prefix('reading/vocabulary')->name('reading.vocabulary.')->group(function () {
        Route::get('/', [ReadingVocabularyController::class, 'index'])->name('index');
        Route::post('/', [ReadingVocabularyController::class, 'store'])->name('store');
        Route::get('/{id}', [ReadingVocabularyController::class, 'show'])->name('show');
        Route::put('/{id}', [ReadingVocabularyController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReadingVocabularyController::class, 'destroy'])->name('destroy');
    });
});