<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\Test;
use App\Models\SpeakingSubmission;
use App\Models\SpeakingRecording;
use App\Models\SpeakingReview;
use App\Helpers\FileUploadHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class SpeakingSubmissionService
{
    private SpeechToTextService $speechToTextService;

    public function __construct(SpeechToTextService $speechToTextService)
    {
        $this->speechToTextService = $speechToTextService;
    }

    public function getSubmissions(array $filters = []): LengthAwarePaginator
    {
        return SpeakingSubmission::with([
            'test:id,title,difficulty',
            'student:id,name,email',
            'review.teacher:id,name',
            'recordings'
        ])
            ->when($filters['test_id'] ?? null, fn($q, $testId) => $q->where('test_id', $testId))
            ->when($filters['student_id'] ?? null, fn($q, $studentId) => $q->where('student_id', $studentId))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['teacher_id'] ?? null, function ($q, $teacherId) {
                $q->whereHas('test', fn($query) => $query->where('creator_id', $teacherId));
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getStudentDashboard(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return SpeakingSubmission::with([
            'test:id,title,description,difficulty',
            'review:id,score,feedback,reviewed_at'
        ])
            ->where('student_id', $studentId)
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getTeacherReviewQueue(string $teacherId, array $filters = []): LengthAwarePaginator
    {
        return SpeakingSubmission::with([
            'test:id,title,difficulty',
            'student:id,name,email',
            'recordings'
        ])
            ->whereHas('test', fn($q) => $q->where('creator_id', $teacherId))
            ->where('status', 'submitted')
            ->when($filters['test_id'] ?? null, fn($q, $testId) => $q->where('test_id', $testId))
            ->latest('submitted_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function startSubmission(Test $test, string $studentId, array $data): SpeakingSubmission
    {
        // Check if student can attempt this test
        $this->validateSubmissionAttempt($test, $studentId, $data['attempt_number'] ?? 1);

        return SpeakingSubmission::create([
            'test_id' => $test->id,
            'student_id' => $studentId,
            'attempt_number' => $data['attempt_number'] ?? 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function uploadRecording(array $data): SpeakingRecording
    {
        $submission = SpeakingSubmission::findOrFail($data['submission_id']);
        
        if ($submission->status !== 'in_progress') {
            throw new Exception('Cannot upload recording for submission that is not in progress');
        }

        return DB::transaction(function () use ($submission, $data) {
            // Upload the audio file
            $audioFile = $data['audio_file'];
            $fileName = $this->generateFileName($submission, $data['question_id'], $audioFile);
            $filePath = $audioFile->storeAs('speaking_recordings', $fileName, 'speaking_recordings');

            // Get file info
            $fileSize = $audioFile->getSize();
            $duration = $data['duration_seconds'] ?? null;

            // Create recording record first
            $recording = SpeakingRecording::create([
                'submission_id' => $submission->id,
                'question_id' => $data['question_id'],
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'duration_seconds' => $duration,
                'speech_processed' => false,
            ]);

            // Process speech-to-text asynchronously
            try {
                $speechData = $this->speechToTextService->processRecording($recording);
                
                $recording->update([
                    'transcript' => $speechData['transcript'],
                    'confidence_score' => $speechData['confidence_score'],
                    'fluency_score' => $speechData['fluency_score'],
                    'speaking_rate' => $speechData['speaking_rate'],
                    'pause_analysis' => $speechData['pause_analysis'],
                    'speech_processed' => true,
                    'speech_processed_at' => now(),
                ]);
            } catch (Exception $e) {
                Log::error('Speech processing failed for recording', [
                    'recording_id' => $recording->id,
                    'error' => $e->getMessage()
                ]);
                // Don't throw error, recording is still valid without speech processing
            }

            return $recording;
        });
    }

    public function submitSpeaking(SpeakingSubmission $submission): SpeakingSubmission
    {
        if ($submission->status !== 'in_progress') {
            throw new Exception('Cannot submit submission that is not in progress');
        }

        $submission->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'total_time_seconds' => $this->calculateTotalTime($submission),
        ]);

        return $submission;
    }

    public function reviewSubmission(SpeakingSubmission $submission, array $reviewData, string $teacherId): SpeakingReview
    {
        if ($submission->status !== 'submitted') {
            throw new Exception('Can only review submitted submissions');
        }

        return DB::transaction(function () use ($submission, $reviewData, $teacherId) {
            // Create or update review
            $review = SpeakingReview::updateOrCreate(
                ['submission_id' => $submission->id],
                [
                    'teacher_id' => $teacherId,
                    'overall_score' => $reviewData['overall_score'],
                    'pronunciation_score' => $reviewData['pronunciation_score'] ?? null,
                    'fluency_score' => $reviewData['fluency_score'] ?? null,
                    'grammar_score' => $reviewData['grammar_score'] ?? null,
                    'vocabulary_score' => $reviewData['vocabulary_score'] ?? null,
                    'content_score' => $reviewData['content_score'] ?? null,
                    'feedback' => $reviewData['feedback'] ?? null,
                    'detailed_comments' => $reviewData['detailed_comments'] ?? null,
                    'strengths' => $reviewData['strengths'] ?? null,
                    'areas_for_improvement' => $reviewData['areas_for_improvement'] ?? null,
                    'reviewed_at' => now(),
                ]
            );

            // Update submission status
            $submission->update([
                'status' => 'reviewed'
            ]);

            return $review;
        });
    }

    public function getSubmissionForReview(string $submissionId): SpeakingSubmission
    {
        return SpeakingSubmission::with([
            'assignment.test',
            'assignment.class',
            'student:id,name,email',
            'recordings.question',
            'review'
        ])->findOrFail($submissionId);
    }

    public function getSubmissionWithDetails(string $submissionId): SpeakingSubmission
    {
        return SpeakingSubmission::with([
            'assignment.test.sections.questions',
            'student:id,name,email',
            'recordings.question',
            'review.teacher:id,name'
        ])->findOrFail($submissionId);
    }

    public function deleteRecording(SpeakingRecording $recording): bool
    {
        return DB::transaction(function () use ($recording) {
            // Delete file from storage
            if ($recording->file_path && Storage::disk('speaking_recordings')->exists($recording->file_path)) {
                Storage::disk('speaking_recordings')->delete($recording->file_path);
            }

            return $recording->delete();
        });
    }

    public function getStudentSubmissions(string $studentId, array $filters = []): Collection
    {
        return SpeakingSubmission::with([
            'assignment.test:id,title,difficulty',
            'review:id,submission_id,overall_score'
        ])
            ->where('student_id', $studentId)
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    private function validateSubmissionAttempt(Test $test, string $studentId, int $attemptNumber): void
    {
        // Check if test allows repetition
        if ($attemptNumber > 1 && !$test->allow_repetition) {
            throw new Exception('This test does not allow multiple attempts');
        }

        // Check max attempts
        if ($test->max_repetition_count && $attemptNumber > $test->max_repetition_count) {
            throw new Exception("Maximum number of attempts ({$test->max_repetition_count}) exceeded");
        }

        // Check if this specific attempt already exists
        $existingSubmission = SpeakingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existingSubmission) {
            throw new Exception('This attempt has already been started');
        }
    }

    private function generateFileName(SpeakingSubmission $submission, string $questionId, $audioFile): string
    {
        $timestamp = now()->format('YmdHis');
        $extension = $audioFile->getClientOriginalExtension();

        return "submission_{$submission->id}_question_{$questionId}_{$timestamp}.{$extension}";
    }

    private function calculateTotalTime(SpeakingSubmission $submission): ?int
    {
        if (!$submission->started_at || !$submission->submitted_at) {
            return null;
        }

        return $submission->started_at->diffInSeconds($submission->submitted_at);
    }
}