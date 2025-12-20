<?php

namespace App\Services\V1\ReadingTest;

use App\Models\Test;
use App\Models\ReadingSubmission;
use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class ReadingSubmissionService
{
    /**
     * Get student's reading test submissions
     */
    public function getStudentSubmissions(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return ReadingSubmission::with(['test:id,title,difficulty,type', 'answers'])
            ->where('student_id', $studentId)
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['test_type'] ?? null, function($q, $type) {
                $q->whereHas('test', fn($query) => $query->where('type', $type));
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Start a new reading test attempt
     */
    public function startTest(Test $test, string $studentId, array $data): ReadingSubmission
    {
        return DB::transaction(function () use ($test, $studentId, $data) {
            // Validate test type
            if ($test->type !== 'reading') {
                throw new Exception('Invalid test type for reading submission');
            }

            // Check if student can attempt this test
            $this->validateTestAttempt($test, $studentId, $data['attempt_number'] ?? 1);

            // Create submission
            $submission = ReadingSubmission::create([
                'test_id' => $test->id,
                'student_id' => $studentId,
                'attempt_number' => $data['attempt_number'] ?? 1,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // Initialize answer records for all questions
            $this->initializeAnswers($submission);

            return $submission->load('test', 'answers');
        });
    }

    /**
     * Get test details formatted for student
     */
    public function getTestForStudent(Test $test, string $studentId): Test
    {
        // Check if student has existing submission
        $existingSubmission = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        $test->load([
            'passages.questionGroups.testQuestions.questionOptions',
            'passages.questionGroups.testQuestions.highlightSegments'
        ]);

        // Add student-specific data
        $test->existing_submission = $existingSubmission;
        $test->can_attempt = $this->canStudentAttempt($test, $studentId);
        $test->next_attempt_number = $this->getNextAttemptNumber($test, $studentId);

        return $test;
    }

    /**
     * Get submission with detailed data
     */
    public function getSubmissionWithDetails(ReadingSubmission $submission): ReadingSubmission
    {
        return $submission->load([
            'test.passages.questionGroups.testQuestions.questionOptions',
            'test.passages.questionGroups.testQuestions.highlightSegments',
            'answers.question',
            'vocabularyDiscoveries.vocabulary'
        ]);
    }

    /**
     * Initialize answer records for all questions in the test
     */
    private function initializeAnswers(ReadingSubmission $submission): void
    {
        $questions = TestQuestion::whereHas('questionGroup.passage', function ($query) use ($submission) {
            $query->where('test_id', $submission->test_id);
        })->get();

        foreach ($questions as $question) {
            $submission->answers()->create([
                'question_id' => $question->id,
                'student_answer' => null,
                'correct_answer' => $question->correct_answers,
                'is_correct' => null,
                'points_earned' => 0,
            ]);
        }
    }

    /**
     * Validate if student can attempt the test
     */
    private function validateTestAttempt(Test $test, string $studentId, int $attemptNumber): void
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
        $existingAttempt = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existingAttempt) {
            throw new Exception('This attempt has already been started');
        }
    }

    /**
     * Check if student can attempt the test
     */
    private function canStudentAttempt(Test $test, string $studentId): bool
    {
        if (!$test->allow_repetition) {
            $existingSubmission = ReadingSubmission::where('test_id', $test->id)
                ->where('student_id', $studentId)
                ->exists();
            
            return !$existingSubmission;
        }

        if ($test->max_repetition_count) {
            $attemptCount = ReadingSubmission::where('test_id', $test->id)
                ->where('student_id', $studentId)
                ->count();
            
            return $attemptCount < $test->max_repetition_count;
        }

        return true;
    }

    /**
     * Get next attempt number for student
     */
    private function getNextAttemptNumber(Test $test, string $studentId): int
    {
        $lastAttempt = ReadingSubmission::where('test_id', $test->id)
            ->where('student_id', $studentId)
            ->max('attempt_number');

        return ($lastAttempt ?? 0) + 1;
    }
}