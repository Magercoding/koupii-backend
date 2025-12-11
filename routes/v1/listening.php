<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Listening\ListeningSubmissionController;
use App\Http\Controllers\V1\Listening\ListeningAnswerController;
use App\Http\Controllers\V1\Listening\ListeningAudioController;

/**
 * Listening Test Routes
 */
Route::middleware(['auth:sanctum'])->prefix('listening')->name('listening.')->group(function () {
    
    // Test Management
    Route::get('tests/{test}', [ListeningSubmissionController::class, 'show'])
        ->name('tests.show');
    
    Route::post('tests/{test}/start', [ListeningSubmissionController::class, 'start'])
        ->name('tests.start');
    
    // Submission Management
    Route::get('submissions', [ListeningSubmissionController::class, 'index'])
        ->name('submissions.index');
    
    Route::get('submissions/{submission}', [ListeningSubmissionController::class, 'getSubmission'])
        ->name('submissions.show');
    
    Route::post('submissions/{submission}/submit', [ListeningSubmissionController::class, 'submit'])
        ->name('submissions.submit');
    
    // Answer Management
    Route::get('submissions/{submission}/answers', [ListeningAnswerController::class, 'index'])
        ->name('submissions.answers.index');
    
    Route::post('submissions/{submission}/answers', [ListeningAnswerController::class, 'store'])
        ->name('submissions.answers.store');
    
    Route::get('answers/{answer}', [ListeningAnswerController::class, 'show'])
        ->name('answers.show');
    
    Route::put('answers/{answer}', [ListeningAnswerController::class, 'update'])
        ->name('answers.update');
    
    // Audio Management
    Route::post('submissions/{submission}/audio/play', [ListeningAudioController::class, 'logPlay'])
        ->name('submissions.audio.play');
    
    Route::get('submissions/{submission}/audio/logs', [ListeningAudioController::class, 'getLogs'])
        ->name('submissions.audio.logs');
    
    Route::get('submissions/{submission}/audio/stats', [ListeningAudioController::class, 'getStats'])
        ->name('submissions.audio.stats');
    
    Route::get('audio/segments/{segment}', [ListeningAudioController::class, 'getSegment'])
        ->name('audio.segments.show');
});