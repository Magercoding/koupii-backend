<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Listening\{
    ListeningTaskController,
    ListeningQuestionController,
    ListeningAudioController,
    ListeningSubmissionController,
    ListeningAnswerController,
    ListeningAnalyticsController
};

/*
|--------------------------------------------------------------------------
| Listening Module API Routes
|--------------------------------------------------------------------------
| 
| Clean and organized routes for listening functionality
| Separated by controller with RESTful patterns
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // === LISTENING TASKS ROUTES ===
    Route::prefix('listening/tasks')->group(function () {
        Route::get('/', [ListeningTaskController::class, 'index']); // GET /api/v1/listening/tasks
        Route::post('/', [ListeningTaskController::class, 'store']); // POST /api/v1/listening/tasks
        Route::get('/{listeningTask}', [ListeningTaskController::class, 'show']); // GET /api/v1/listening/tasks/{id}
        Route::put('/{listeningTask}', [ListeningTaskController::class, 'update']); // PUT /api/v1/listening/tasks/{id}
        Route::delete('/{listeningTask}', [ListeningTaskController::class, 'destroy']); // DELETE /api/v1/listening/tasks/{id}
        
        // Task Management
        Route::post('/{listeningTask}/duplicate', [ListeningTaskController::class, 'duplicate']); // POST /api/v1/listening/tasks/{id}/duplicate
        Route::patch('/{listeningTask}/publish', [ListeningTaskController::class, 'publish']); // PATCH /api/v1/listening/tasks/{id}/publish
        Route::patch('/{listeningTask}/unpublish', [ListeningTaskController::class, 'unpublish']); // PATCH /api/v1/listening/tasks/{id}/unpublish
        Route::post('/{listeningTask}/assign', [ListeningTaskController::class, 'assign']); // POST /api/v1/listening/tasks/{id}/assign
        
        // Bulk Operations
        Route::post('/bulk/create', [ListeningTaskController::class, 'bulkCreate']); // POST /api/v1/listening/tasks/bulk/create
        Route::patch('/bulk/update', [ListeningTaskController::class, 'bulkUpdate']); // PATCH /api/v1/listening/tasks/bulk/update
        Route::delete('/bulk/delete', [ListeningTaskController::class, 'bulkDelete']); // DELETE /api/v1/listening/tasks/bulk/delete
        
        // Preview & Testing
        Route::get('/{listeningTask}/preview', [ListeningTaskController::class, 'preview']); // GET /api/v1/listening/tasks/{id}/preview
        Route::post('/{listeningTask}/test', [ListeningTaskController::class, 'test']); // POST /api/v1/listening/tasks/{id}/test
    });

    // === LISTENING QUESTIONS ROUTES ===
    Route::prefix('listening/questions')->group(function () {
        Route::get('/', [ListeningQuestionController::class, 'index']); // GET /api/v1/listening/questions
        Route::post('/', [ListeningQuestionController::class, 'store']); // POST /api/v1/listening/questions
        Route::get('/{testQuestion}', [ListeningQuestionController::class, 'show']); // GET /api/v1/listening/questions/{id}
        Route::put('/{testQuestion}', [ListeningQuestionController::class, 'update']); // PUT /api/v1/listening/questions/{id}
        Route::delete('/{testQuestion}', [ListeningQuestionController::class, 'destroy']); // DELETE /api/v1/listening/questions/{id}
        
        // Question Management
        Route::post('/{testQuestion}/duplicate', [ListeningQuestionController::class, 'duplicate']); // POST /api/v1/listening/questions/{id}/duplicate
        Route::patch('/{testQuestion}/reorder', [ListeningQuestionController::class, 'reorder']); // PATCH /api/v1/listening/questions/{id}/reorder
        
        // Question Type Operations
        Route::get('/types/supported', [ListeningQuestionController::class, 'getSupportedTypes']); // GET /api/v1/listening/questions/types/supported
        Route::get('/types/{questionType}/template', [ListeningQuestionController::class, 'getQuestionTemplate']); // GET /api/v1/listening/questions/types/{type}/template
        Route::post('/types/{questionType}/validate', [ListeningQuestionController::class, 'validateQuestionData']); // POST /api/v1/listening/questions/types/{type}/validate
        
        // Bulk Operations
        Route::post('/bulk/create', [ListeningQuestionController::class, 'bulkCreate']); // POST /api/v1/listening/questions/bulk/create
        Route::patch('/bulk/update', [ListeningQuestionController::class, 'bulkUpdate']); // PATCH /api/v1/listening/questions/bulk/update
        Route::delete('/bulk/delete', [ListeningQuestionController::class, 'bulkDelete']); // DELETE /api/v1/listening/questions/bulk/delete
        Route::patch('/bulk/reorder', [ListeningQuestionController::class, 'bulkReorder']); // PATCH /api/v1/listening/questions/bulk/reorder
        
        // Preview
        Route::get('/{testQuestion}/preview', [ListeningQuestionController::class, 'preview']); // GET /api/v1/listening/questions/{id}/preview
    });

    // === LISTENING AUDIO ROUTES ===
    Route::prefix('listening/audio')->group(function () {
        // Audio File Management
        Route::post('/upload', [ListeningAudioController::class, 'uploadAudio']); // POST /api/v1/listening/audio/upload
        Route::post('/process', [ListeningAudioController::class, 'processAudio']); // POST /api/v1/listening/audio/process
        Route::post('/validate', [ListeningAudioController::class, 'validateAudio']); // POST /api/v1/listening/audio/validate
        
        // Audio Details & Metadata
        Route::get('/tasks/{listeningTask}/details', [ListeningAudioController::class, 'getAudioDetails']); // GET /api/v1/listening/audio/tasks/{id}/details
        Route::get('/tasks/{listeningTask}/metadata', [ListeningAudioController::class, 'getAudioMetadata']); // GET /api/v1/listening/audio/tasks/{id}/metadata
        Route::get('/tasks/{listeningTask}/waveform', [ListeningAudioController::class, 'getWaveform']); // GET /api/v1/listening/audio/tasks/{id}/waveform
        
        // Audio Segments
        Route::get('/tasks/{listeningTask}/segments', [ListeningAudioController::class, 'getAudioSegments']); // GET /api/v1/listening/audio/tasks/{id}/segments
        Route::post('/tasks/{listeningTask}/segments', [ListeningAudioController::class, 'createSegments']); // POST /api/v1/listening/audio/tasks/{id}/segments
        Route::put('/tasks/{listeningTask}/segments', [ListeningAudioController::class, 'updateSegments']); // PUT /api/v1/listening/audio/tasks/{id}/segments
        Route::delete('/tasks/{listeningTask}/segments', [ListeningAudioController::class, 'deleteSegments']); // DELETE /api/v1/listening/audio/tasks/{id}/segments
        Route::get('/segments/{segment}', [ListeningAudioController::class, 'getSegment']); // GET /api/v1/listening/audio/segments/{id}
        
        // Transcript Management
        Route::post('/tasks/{listeningTask}/transcript/generate', [ListeningAudioController::class, 'generateTranscript']); // POST /api/v1/listening/audio/tasks/{id}/transcript/generate
        
        // Audio Interaction Logs
        Route::post('/submissions/{submission}/logs', [ListeningAudioController::class, 'logPlay']); // POST /api/v1/listening/audio/submissions/{id}/logs
        Route::get('/submissions/{submission}/logs', [ListeningAudioController::class, 'getLogs']); // GET /api/v1/listening/audio/submissions/{id}/logs
        Route::get('/submissions/{submission}/stats', [ListeningAudioController::class, 'getStats']); // GET /api/v1/listening/audio/submissions/{id}/stats
    });

    // === LISTENING SUBMISSIONS ROUTES ===
    Route::prefix('listening/submissions')->group(function () {
        Route::get('/', [ListeningSubmissionController::class, 'index']); // GET /api/v1/listening/submissions
        Route::post('/', [ListeningSubmissionController::class, 'store']); // POST /api/v1/listening/submissions
        Route::get('/{listeningSubmission}', [ListeningSubmissionController::class, 'show']); // GET /api/v1/listening/submissions/{id}
        Route::put('/{listeningSubmission}', [ListeningSubmissionController::class, 'update']); // PUT /api/v1/listening/submissions/{id}
        Route::delete('/{listeningSubmission}', [ListeningSubmissionController::class, 'destroy']); // DELETE /api/v1/listening/submissions/{id}
        
        // Submission Actions
        Route::post('/{listeningSubmission}/submit', [ListeningSubmissionController::class, 'submit']); // POST /api/v1/listening/submissions/{id}/submit
        Route::post('/{listeningSubmission}/grade', [ListeningSubmissionController::class, 'grade']); // POST /api/v1/listening/submissions/{id}/grade
        Route::patch('/{listeningSubmission}/reset', [ListeningSubmissionController::class, 'reset']); // PATCH /api/v1/listening/submissions/{id}/reset
        
        // Submission Analysis
        Route::get('/{listeningSubmission}/analysis', [ListeningSubmissionController::class, 'analyze']); // GET /api/v1/listening/submissions/{id}/analysis
        Route::get('/{listeningSubmission}/feedback', [ListeningSubmissionController::class, 'getFeedback']); // GET /api/v1/listening/submissions/{id}/feedback
        Route::post('/{listeningSubmission}/feedback', [ListeningSubmissionController::class, 'addFeedback']); // POST /api/v1/listening/submissions/{id}/feedback
        
        // Auto-save
        Route::patch('/{listeningSubmission}/autosave', [ListeningSubmissionController::class, 'autoSave']); // PATCH /api/v1/listening/submissions/{id}/autosave
        
        // Export
        Route::get('/{listeningSubmission}/export', [ListeningSubmissionController::class, 'export']); // GET /api/v1/listening/submissions/{id}/export
    });

    // === LISTENING ANSWERS ROUTES ===
    Route::prefix('listening/answers')->group(function () {
        Route::get('/', [ListeningAnswerController::class, 'index']); // GET /api/v1/listening/answers
        Route::post('/', [ListeningAnswerController::class, 'store']); // POST /api/v1/listening/answers
        Route::get('/{listeningQuestionAnswer}', [ListeningAnswerController::class, 'show']); // GET /api/v1/listening/answers/{id}
        Route::put('/{listeningQuestionAnswer}', [ListeningAnswerController::class, 'update']); // PUT /api/v1/listening/answers/{id}
        Route::delete('/{listeningQuestionAnswer}', [ListeningAnswerController::class, 'destroy']); // DELETE /api/v1/listening/answers/{id}
        
        // Answer Operations
        Route::post('/validate', [ListeningAnswerController::class, 'validateAnswer']); // POST /api/v1/listening/answers/validate
        Route::post('/grade', [ListeningAnswerController::class, 'gradeAnswer']); // POST /api/v1/listening/answers/grade
        
        // Bulk Operations
        Route::post('/bulk/submit', [ListeningAnswerController::class, 'bulkSubmit']); // POST /api/v1/listening/answers/bulk/submit
        Route::post('/bulk/grade', [ListeningAnswerController::class, 'bulkGrade']); // POST /api/v1/listening/answers/bulk/grade
        Route::patch('/bulk/update', [ListeningAnswerController::class, 'bulkUpdate']); // PATCH /api/v1/listening/answers/bulk/update
        
        // Answer Analysis
        Route::get('/{listeningQuestionAnswer}/analysis', [ListeningAnswerController::class, 'analyze']); // GET /api/v1/listening/answers/{id}/analysis
    });

    // === LISTENING ANALYTICS ROUTES ===
    Route::prefix('listening/analytics')->group(function () {
        // Task Analytics
        Route::get('/tasks/{listeningTask}', [ListeningAnalyticsController::class, 'getTaskAnalytics']); // GET /api/v1/listening/analytics/tasks/{id}
        
        // Student Analytics
        Route::get('/students/{student}', [ListeningAnalyticsController::class, 'getStudentAnalytics']); // GET /api/v1/listening/analytics/students/{id}
        Route::get('/students/{student}/progress', [ListeningAnalyticsController::class, 'getProgressAnalytics']); // GET /api/v1/listening/analytics/students/{id}/progress
        
        // Question Type Analytics
        Route::get('/question-types', [ListeningAnalyticsController::class, 'getQuestionTypeAnalytics']); // GET /api/v1/listening/analytics/question-types
        
        // Audio Analytics
        Route::get('/audio', [ListeningAnalyticsController::class, 'getAudioAnalytics']); // GET /api/v1/listening/analytics/audio
        
        // Comparative Analytics
        Route::get('/comparative', [ListeningAnalyticsController::class, 'getComparativeAnalytics']); // GET /api/v1/listening/analytics/comparative
        
        // Reports & Dashboard
        Route::post('/reports', [ListeningAnalyticsController::class, 'generateReport']); // POST /api/v1/listening/analytics/reports
        Route::get('/dashboard', [ListeningAnalyticsController::class, 'getDashboardData']); // GET /api/v1/listening/analytics/dashboard
        
        // Data Export
        Route::post('/export', [ListeningAnalyticsController::class, 'exportData']); // POST /api/v1/listening/analytics/export
    });
});