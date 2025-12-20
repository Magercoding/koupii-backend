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
use Exception;

class SpeakingSubmissionService
{
    public function getSubmissions(array $filters = []): LengthAwarePaginator
    {
        return SpeakingSubmission::with([
            'test:id,title,difficulty',
            'student:id,name,email',
            'review.teacher:id,name'
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

    public function uploadRecording(SpeakingSubmission $submission, array $data): SpeakingRecording
    {
        if ($submission->status !== 'in_progress') {
            throw new Exception('Cannot upload recording for submission that is not in progress');
        }

        return DB::transaction(function () use ($submission, $data) {
            // Upload the audio file
            $audioFile = $data['audio_file'];
            $fileName = $this->generateFileName($submission, $data['question_id'], $audioFile);
            $filePath = $audioFile->storeAs('speaking_recordings', $fileName, 'public');

            // Create recording record
            return SpeakingRecording::create([
                'submission_id' => $submission->id,
                'question_id' => $data['question_id'],
                'audio_file_path' => $filePath,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'recording_started_at' => $data['recording_started_at'] ?? null,
                'recording_ended_at' => $data['recording_ended_at'] ?? null,
            ]);
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

    public function reviewSubmission(SpeakingSubmission $submission, string $teacherId, array $data): SpeakingReview
    {
        if ($submission->status !== 'submitted') {
            throw new Exception('Cannot review submission that has not been submitted');
        }

        return DB::transaction(function () use ($submission, $teacherId, $data) {
            // Create or update review
            $review = SpeakingReview::updateOrCreate(
                ['submission_id' => $submission->id],
                [
                    'teacher_id' => $teacherId,
                    'total_score' => $data['total_score'],
                    'overall_feedback' => $data['overall_feedback'] ?? null,
                    'question_scores' => $data['question_scores'] ?? null,
                    'reviewed_at' => now(),
                ]
            );

            // Update submission status
            $submission->update(['status' => 'reviewed']);

            return $review;
        });
    }

    public function getSubmissionWithDetails(string $submissionId): SpeakingSubmission
    {
        return SpeakingSubmission::with([
            'test.speakingSections.topics.questions',
            'student:id,name,email',
            'recordings.question',
            'review.teacher:id,name'
        ])->findOrFail($submissionId);
    }

    public function deleteRecording(SpeakingRecording $recording): bool
    {
        return DB::transaction(function () use ($recording) {
            // Delete file from storage
            if ($recording->audio_file_path && Storage::disk('public')->exists($recording->audio_file_path)) {
                Storage::disk('public')->delete($recording->audio_file_path);
            }

            return $recording->delete();
        });
    }

    public function getStudentSubmissions(string $studentId, array $filters = []): Collection
    {
        return SpeakingSubmission::with([
            'test:id,title,difficulty',
            'review:id,submission_id,total_score'
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