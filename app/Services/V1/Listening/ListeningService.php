<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningAudioLog;
use App\Models\ListeningQuestionAnswer;
use App\Models\ListeningSubmission;
use App\Models\Test;
use App\Models\User;
use App\Helpers\Listening\ListeningTestHelper;
use App\Helpers\Listening\ListeningAnalyticsHelper;
use App\Helpers\Listening\ListeningVocabularyHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ListeningService
{
    /**
     * Start a new listening test session for a student
     */
    public function startTest(Test $test, User $student): ListeningSubmission
    {
        // Check if student has ongoing submission
        $ongoingSubmission = ListeningTestHelper::getOngoingSubmission($test, $student);
        if ($ongoingSubmission) {
            return $ongoingSubmission;
        }

        // Validate attempt limits
        $attemptValidation = ListeningTestHelper::validateAttemptLimits($test, $student);
        if (!$attemptValidation['can_attempt']) {
            throw new \Exception('Maximum attempt limit reached for this test');
        }

        return DB::transaction(function () use ($test, $student, $attemptValidation) {
            $submission = ListeningSubmission::create([
                'test_id' => $test->id,
                'student_id' => $student->id,
                'attempt_number' => $attemptValidation['attempt_number'],
                'status' => 'in_progress',
                'started_at' => now(),
                'audio_play_counts' => []
            ]);

            // Initialize question answers
            ListeningTestHelper::initializeQuestionAnswers($submission);

            return $submission;
        });
    }

    /**
     * Get test questions with audio segments
     */
    public function getTestQuestions(ListeningSubmission $submission): Collection
    {
        return $submission->test->testQuestions()
            ->with([
                'options',
                'passage',
                'questionBreakdowns.questionGroup',
                'audioSegments' => function ($query) {
                    $query->ordered();
                }
            ])
            ->orderBy('question_order')
            ->get();
    }

    /**
     * Submit an answer to a listening question
     */
    public function submitAnswer(ListeningSubmission $submission, array $answerData): ListeningQuestionAnswer
    {
        if ($submission->status !== 'in_progress') {
            throw new \Exception('Cannot submit answer to completed test');
        }

        return DB::transaction(function () use ($submission, $answerData) {
            $answer = ListeningQuestionAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'question_id' => $answerData['question_id']
                ],
                [
                    'selected_option_id' => $answerData['selected_option_id'] ?? null,
                    'text_answer' => $answerData['text_answer'] ?? null,
                    'answer_data' => $answerData['answer_data'] ?? null,
                    'time_spent_seconds' => $answerData['time_spent_seconds'] ?? 0
                ]
            );

            // Evaluate the answer
            $answer->evaluateAnswer();

            // Record audio play for this question if provided
            if (isset($answerData['play_count'])) {
                $answer->update(['play_count' => $answerData['play_count']]);
            }

            return $answer;
        });
    }

    /**
     * Submit a listening test with final answers
     */
    public function submitTest(ListeningSubmission $submission, array $submissionData): ListeningSubmission
    {
        if ($submission->status === 'completed') {
            throw new \Exception('Test has already been completed');
        }

        // Check if test can be submitted
        $submitValidation = ListeningTestHelper::canSubmitTest($submission);
        if (!$submitValidation['can_submit']) {
            throw new \Exception('Cannot submit test: ' . implode(', ', $submitValidation['reasons']));
        }

        return DB::transaction(function () use ($submission, $submissionData) {
            // Update submission with additional data
            if (isset($submissionData['time_spent'])) {
                $submission->update(['time_taken_seconds' => $submissionData['time_spent']]);
            }

            // Mark as submitted first
            $submission->update([
                'status' => 'submitted',
                'submitted_at' => now()
            ]);

            // Complete the test (calculate scores, etc.)
            return $this->completeTest($submission);
        });
    }

    /**
     * Complete a listening test and calculate final score
     */
    public function completeTest(ListeningSubmission $submission): ListeningSubmission
    {
        if ($submission->status === 'completed') {
            return $submission;
        }

        return DB::transaction(function () use ($submission) {
            // Calculate time taken if not already set
            if (!$submission->time_taken_seconds) {
                $timeTaken = now()->diffInSeconds($submission->started_at);
                $submission->update(['time_taken_seconds' => $timeTaken]);
            }

            // Calculate final score
            $submission->update([
                'status' => 'completed',
                'submitted_at' => $submission->submitted_at ?? now()
            ]);

            $submission->calculateScore();

            // Process vocabulary discoveries from audio content
            ListeningVocabularyHelper::processVocabularyDiscoveries($submission);

            return $submission->fresh();
        });
    }

    /**
     * Log audio interaction for analytics
     */
    public function logAudioInteraction(ListeningSubmission $submission, array $logData): ListeningAudioLog
    {
        return ListeningAudioLog::create([
            'submission_id' => $submission->id,
            'question_id' => $logData['question_id'],
            'segment_id' => $logData['segment_id'] ?? null,
            'action_type' => $logData['action_type'],
            'timestamp_seconds' => $logData['timestamp_seconds'] ?? 0,
            'duration_listened' => $logData['duration_listened'] ?? 0,
            'playback_speed' => $logData['playback_speed'] ?? 1.0,
            'device_info' => $logData['device_info'] ?? [],
            'logged_at' => now()
        ]);
    }

    /**
     * Get detailed test results with analytics
     */
    public function getDetailedResult(ListeningSubmission $submission): array
    {
        return ListeningAnalyticsHelper::getDetailedResult($submission);
    }

    /**
     * Get student's listening test history
     */
    public function getStudentHistory(User $student, array $filters = []): Collection
    {
        return ListeningAnalyticsHelper::getStudentHistory($student, $filters);
    }

    /**
     * Get listening performance analytics for student
     */
    public function getPerformanceAnalytics(User $student): array
    {
        return ListeningAnalyticsHelper::getPerformanceAnalytics($student);
    }

    /**
     * Get test completion status
     */
    public function getCompletionStatus(ListeningSubmission $submission): array
    {
        $completionPercentage = ListeningTestHelper::calculateCompletionPercentage($submission);
        $timeStats = ListeningTestHelper::getTimeStatistics($submission);
        $submitValidation = ListeningTestHelper::canSubmitTest($submission);

        return [
            'completion_percentage' => $completionPercentage,
            'time_statistics' => $timeStats,
            'can_submit' => $submitValidation['can_submit'],
            'submit_reasons' => $submitValidation['reasons'],
            'status' => $submission->status,
            'is_completed' => $submission->status === 'completed'
        ];
    }
}