<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\SpeakingTask;
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
            'speakingTask:id,title,difficulty_level',
            'student:id,name,email',
            'review.teacher:id,name',
            'recordings'
        ])
            ->when($filters['test_id'] ?? null, fn($q, $testId) => $q->where('test_id', $testId))
            ->when($filters['student_id'] ?? null, fn($q, $studentId) => $q->where('student_id', $studentId))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['teacher_id'] ?? null, function ($q, $teacherId) {
                $q->whereHas('speakingTask', fn($query) => $query->where('created_by', $teacherId));
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getStudentDashboard(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return SpeakingSubmission::with([
            'speakingTask:id,title,description,difficulty_level',
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
            'speakingTask:id,title,difficulty_level',
            'student:id,name,email',
            'recordings'
        ])
            ->whereHas('speakingTask', fn($q) => $q->where('created_by', $teacherId))
            ->where('status', 'submitted')
            ->when($filters['test_id'] ?? null, fn($q, $testId) => $q->where('test_id', $testId))
            ->latest('submitted_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function startSubmission(SpeakingTask $test, string $studentId, array $data): SpeakingSubmission
    {
        $assignmentId = $data['assignment_id'] ?? null;
        $attemptNumber = $data['attempt_number'] ?? 1;

        // Check if student can attempt this test
        $this->validateSubmissionAttempt($test, $studentId, $attemptNumber);

        return DB::transaction(function () use ($test, $studentId, $attemptNumber, $assignmentId) {
            $submission = SpeakingSubmission::create([
                'test_id' => $test->id,
                'student_id' => $studentId,
                'assignment_id' => $assignmentId,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // Sync with StudentAssignment if provided
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $studentId)
                    ->first();
                if ($studentAssignment) {
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_IN_PROGRESS,
                        'started_at' => $studentAssignment->started_at ?? now(),
                        'last_activity_at' => now(),
                        'attempt_number' => $attemptNumber,
                        'attempt_count' => max($studentAssignment->attempt_count, $attemptNumber),
                    ]);
                }
            }

            return $submission;
        });
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
                $speechData = $this->processRecording($recording);
                
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

        DB::transaction(function () use ($submission) {
            $submission->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'total_time_seconds' => $this->calculateTotalTime($submission),
            ]);

            // Sync with StudentAssignment if linked
            if ($submission->assignment_id) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $submission->assignment_id)
                    ->where('student_id', $submission->student_id)
                    ->first();
                if ($studentAssignment) {
                    $studentAssignment->submit();
                }
            }
        });

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
            'assignment.speakingTask',
            'assignment.classroom',
            'student:id,name,email',
            'recordings.question',
            'review'
        ])->findOrFail($submissionId);
    }

    public function getSubmissionWithDetails(string $submissionId): SpeakingSubmission
    {
        return SpeakingSubmission::with([
            'assignment.speakingTask',
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
            'assignment.speakingTask:id,title,difficulty_level',
            'review:id,submission_id,overall_score'
        ])
            ->where('student_id', $studentId)
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    private function validateSubmissionAttempt(SpeakingTask $test, string $studentId, int $attemptNumber): void
    {
        // Verify student has access to this task
        $user = \App\Models\User::find($studentId);
        
        if ($user && $user->role === 'student') {
            // 1. Check StudentAssignment table (New system - direct or group)
            $hasStudentAssignment = \App\Models\StudentAssignment::where('student_id', $studentId)
                ->whereHas('assignment', function ($q) use ($test) {
                    $q->where('task_id', $test->id)
                      ->where('task_type', 'speaking_task');
                })->exists();

            if ($hasStudentAssignment) return;

            // 2. Check Assignment table (Class-based link)
            $hasGlobalAssignment = \App\Models\Assignment::where('task_id', $test->id)
                ->where('task_type', 'speaking_task')
                ->whereHas('class.students', function ($query) use ($studentId) {
                    $query->where('users.id', $studentId);
                })->exists();

            if ($hasGlobalAssignment) return;

            // 3. Fallback: Check classroom-based assignments (Legacy)
            $hasLegacyAccess = $test->assignments()
                ->whereHas('classroom.students', function ($query) use ($studentId) {
                    $query->where('users.id', $studentId);
                })->exists();

            if (!$hasLegacyAccess && !$test->is_published) {
                throw new Exception('Unauthorized access to this task');
            }
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

    /**
     * Process a recording using speech-to-text service
     */
    public function processRecording(\App\Models\SpeakingRecording $recording): array
    {
        // Convert audio to text
        $speechData = $this->speechToTextService->convertAudioToText($recording->file_path);

        if (!$speechData['success']) {
            throw new Exception($speechData['message'] ?? 'Speech-to-text failed');
        }

        // Analyze speech quality
        $analysis = $this->speechToTextService->analyzeSpeechQuality($speechData);

        return [
            'transcript' => $speechData['transcript'],
            'confidence_score' => $analysis['confidence_score'],
            'fluency_score' => $analysis['fluency_score'],
            'speaking_rate' => $analysis['speaking_rate'],
            'pause_analysis' => [
                'pause_count' => $analysis['pause_count'],
                'total_pause_time' => $analysis['total_pause_time'],
                'average_pause_duration' => $analysis['average_pause_duration'],
                'pause_frequency' => $analysis['pause_frequency'],
            ]
        ];
    }
}