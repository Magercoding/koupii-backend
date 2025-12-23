<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\SpeakingTask\{
    SpeakingTaskController,
    SpeakingSubmissionController,
    SpeakingDashboardController,
    SpeakingRecordingController,
    SpeakingReviewController
};

/*
|--------------------------------------------------------------------------
| Speaking Module API Routes
|--------------------------------------------------------------------------
| 
| Clean and organized routes for speaking functionality
| Separated by controller with RESTful patterns
| Includes Google AI speech-to-text integration
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // === SPEAKING DASHBOARD ROUTES ===
    Route::prefix('speaking/dashboard')->group(function () {
        Route::get('/student', [SpeakingDashboardController::class, 'studentDashboard']); // GET /api/v1/speaking/dashboard/student
        Route::get('/teacher', [SpeakingDashboardController::class, 'teacherDashboard'])->middleware('role:admin,teacher'); // GET /api/v1/speaking/dashboard/teacher
        Route::get('/tasks/{assignment}', [SpeakingDashboardController::class, 'getTaskDetail']); // GET /api/v1/speaking/dashboard/tasks/{id}
    });

    // === SPEAKING TASKS ROUTES ===
    Route::prefix('speaking/tasks')->group(function () {
        Route::get('/', [SpeakingTaskController::class, 'index']); // GET /api/v1/speaking/tasks
        Route::post('/', [SpeakingTaskController::class, 'store'])->middleware('role:admin,teacher'); // POST /api/v1/speaking/tasks
        Route::get('/{speakingTask}', [SpeakingTaskController::class, 'show']); // GET /api/v1/speaking/tasks/{id}
        Route::put('/{speakingTask}', [SpeakingTaskController::class, 'update'])->middleware('role:admin,teacher'); // PUT /api/v1/speaking/tasks/{id}
        Route::delete('/{speakingTask}', [SpeakingTaskController::class, 'destroy'])->middleware('role:admin,teacher'); // DELETE /api/v1/speaking/tasks/{id}
        
        // Task Management
        Route::post('/{speakingTask}/duplicate', [SpeakingTaskController::class, 'duplicate'])->middleware('role:admin,teacher'); // POST /api/v1/speaking/tasks/{id}/duplicate
        Route::patch('/{speakingTask}/publish', [SpeakingTaskController::class, 'publish'])->middleware('role:admin,teacher'); // PATCH /api/v1/speaking/tasks/{id}/publish
        Route::patch('/{speakingTask}/unpublish', [SpeakingTaskController::class, 'unpublish'])->middleware('role:admin,teacher'); // PATCH /api/v1/speaking/tasks/{id}/unpublish
        Route::post('/{speakingTask}/assign', [SpeakingTaskController::class, 'assign'])->middleware('role:admin,teacher'); // POST /api/v1/speaking/tasks/{id}/assign
    });

    // === SPEAKING SUBMISSIONS ROUTES ===
    Route::prefix('speaking/submissions')->group(function () {
        Route::get('/', [SpeakingSubmissionController::class, 'index']); // GET /api/v1/speaking/submissions
        Route::post('/start', [SpeakingSubmissionController::class, 'startSubmission']); // POST /api/v1/speaking/submissions/start
        Route::get('/{submission}', [SpeakingSubmissionController::class, 'show']); // GET /api/v1/speaking/submissions/{id}
        Route::patch('/{submission}/submit', [SpeakingSubmissionController::class, 'submitForReview']); // PATCH /api/v1/speaking/submissions/{id}/submit
        
        // Teacher routes for review queue
        Route::prefix('review-queue')->middleware('role:admin,teacher')->group(function () {
            Route::get('/', [SpeakingSubmissionController::class, 'getTeacherReviewQueue']); // GET /api/v1/speaking/submissions/review-queue
            Route::get('/class/{classId}', [SpeakingSubmissionController::class, 'getClassReviewQueue']); // GET /api/v1/speaking/submissions/review-queue/class/{id}
        });
    });

    // === SPEAKING RECORDINGS ROUTES ===
    Route::prefix('speaking/recordings')->group(function () {
        Route::post('/upload', [SpeakingRecordingController::class, 'uploadRecording']); // POST /api/v1/speaking/recordings/upload
        Route::get('/{recording}', [SpeakingRecordingController::class, 'show']); // GET /api/v1/speaking/recordings/{id}
        Route::get('/{recording}/download', [SpeakingRecordingController::class, 'download']); // GET /api/v1/speaking/recordings/{id}/download
        Route::delete('/{recording}', [SpeakingRecordingController::class, 'destroy']); // DELETE /api/v1/speaking/recordings/{id}
        
        // Speech-to-text processing
        Route::post('/{recording}/process-speech', [SpeakingRecordingController::class, 'processSpeech']); // POST /api/v1/speaking/recordings/{id}/process-speech
        Route::get('/{recording}/transcript', [SpeakingRecordingController::class, 'getTranscript']); // GET /api/v1/speaking/recordings/{id}/transcript
        Route::get('/{recording}/analysis', [SpeakingRecordingController::class, 'getSpeechAnalysis']); // GET /api/v1/speaking/recordings/{id}/analysis
    });

    // === SPEAKING REVIEWS ROUTES ===
    Route::prefix('speaking/reviews')->middleware('role:admin,teacher')->group(function () {
        Route::post('/', [SpeakingReviewController::class, 'store']); // POST /api/v1/speaking/reviews
        Route::get('/{review}', [SpeakingReviewController::class, 'show']); // GET /api/v1/speaking/reviews/{id}
        Route::put('/{review}', [SpeakingReviewController::class, 'update']); // PUT /api/v1/speaking/reviews/{id}
        Route::delete('/{review}', [SpeakingReviewController::class, 'destroy']); // DELETE /api/v1/speaking/reviews/{id}
        
        // Review management
        Route::patch('/{review}/publish', [SpeakingReviewController::class, 'publishReview']); // PATCH /api/v1/speaking/reviews/{id}/publish
        Route::post('/{review}/add-comment', [SpeakingReviewController::class, 'addComment']); // POST /api/v1/speaking/reviews/{id}/add-comment
    });

    // === BULK OPERATIONS ===
    Route::prefix('speaking/bulk')->middleware('role:admin,teacher')->group(function () {
        Route::post('/assign-tasks', [SpeakingTaskController::class, 'bulkAssign']); // POST /api/v1/speaking/bulk/assign-tasks
        Route::patch('/review-submissions', [SpeakingReviewController::class, 'bulkReview']); // PATCH /api/v1/speaking/bulk/review-submissions
        Route::post('/process-recordings', [SpeakingRecordingController::class, 'bulkProcessSpeech']); // POST /api/v1/speaking/bulk/process-recordings
    });

    // === ANALYTICS AND REPORTS ===
    Route::prefix('speaking/analytics')->middleware('role:admin,teacher')->group(function () {
        Route::get('/class/{classId}', [SpeakingDashboardController::class, 'getClassAnalytics']); // GET /api/v1/speaking/analytics/class/{id}
        Route::get('/student/{studentId}', [SpeakingDashboardController::class, 'getStudentAnalytics']); // GET /api/v1/speaking/analytics/student/{id}
        Route::get('/speech-quality', [SpeakingDashboardController::class, 'getSpeechQualityReport']); // GET /api/v1/speaking/analytics/speech-quality
    });
});