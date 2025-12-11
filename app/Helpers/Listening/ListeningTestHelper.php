<?php

namespace App\Helpers\Listening;

use App\Models\ListeningSubmission;
use App\Models\Test;
use App\Models\User;
use App\Models\ListeningQuestionAnswer;

class ListeningTestHelper
{
    /**
     * Validate test attempt limits
     */
    public static function validateAttemptLimits(Test $test, User $student): array
    {
        $attemptNumber = ListeningSubmission::where('test_id', $test->id)
            ->where('student_id', $student->id)
            ->count() + 1;

        $canAttempt = !$test->max_repetition_count || $attemptNumber <= $test->max_repetition_count;

        return [
            'can_attempt' => $canAttempt,
            'attempt_number' => $attemptNumber,
            'max_attempts' => $test->max_repetition_count,
            'remaining_attempts' => $canAttempt ? 
                ($test->max_repetition_count ? $test->max_repetition_count - $attemptNumber + 1 : null) : 0
        ];
    }

    /**
     * Check if student has ongoing submission
     */
    public static function getOngoingSubmission(Test $test, User $student): ?ListeningSubmission
    {
        return ListeningSubmission::where('test_id', $test->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();
    }

    /**
     * Calculate test completion percentage
     */
    public static function calculateCompletionPercentage(ListeningSubmission $submission): float
    {
        $totalQuestions = $submission->test->testQuestions()->count();
        
        if ($totalQuestions === 0) {
            return 0;
        }

        $answeredQuestions = $submission->answers()
            ->where(function ($query) {
                $query->whereNotNull('selected_option_id')
                      ->orWhereNotNull('text_answer')
                      ->orWhereNotNull('answer_data');
            })
            ->count();

        return ($answeredQuestions / $totalQuestions) * 100;
    }

    /**
     * Get test time statistics
     */
    public static function getTimeStatistics(ListeningSubmission $submission): array
    {
        $test = $submission->test;
        $timeSpent = $submission->time_taken_seconds ?? 0;
        $timeLimit = $test->time_limit_minutes ? $test->time_limit_minutes * 60 : null;

        return [
            'time_spent_seconds' => $timeSpent,
            'time_spent_formatted' => static::formatTime($timeSpent),
            'time_limit_seconds' => $timeLimit,
            'time_limit_formatted' => $timeLimit ? static::formatTime($timeLimit) : null,
            'time_remaining_seconds' => $timeLimit ? max(0, $timeLimit - $timeSpent) : null,
            'time_efficiency_percentage' => $timeLimit && $timeLimit > 0 ? 
                min(100, ($timeSpent / $timeLimit) * 100) : null,
            'is_overtime' => $timeLimit ? $timeSpent > $timeLimit : false
        ];
    }

    /**
     * Format time in seconds to human readable format
     */
    private static function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if test can be submitted
     */
    public static function canSubmitTest(ListeningSubmission $submission): array
    {
        $completionPercentage = static::calculateCompletionPercentage($submission);
        $timeStats = static::getTimeStatistics($submission);
        $test = $submission->test;

        $canSubmit = true;
        $reasons = [];

        // Check if test is already completed
        if ($submission->status === 'completed') {
            $canSubmit = false;
            $reasons[] = 'Test has already been completed';
        }

        // Check minimum completion percentage (if required)
        if ($test->minimum_completion_percentage && $completionPercentage < $test->minimum_completion_percentage) {
            $canSubmit = false;
            $reasons[] = "Minimum completion percentage not met ({$completionPercentage}% < {$test->minimum_completion_percentage}%)";
        }

        // Check if time limit has been exceeded (if enforced)
        if ($test->enforce_time_limit && $timeStats['is_overtime']) {
            $canSubmit = false;
            $reasons[] = 'Time limit exceeded';
        }

        return [
            'can_submit' => $canSubmit,
            'reasons' => $reasons,
            'completion_percentage' => $completionPercentage,
            'time_statistics' => $timeStats
        ];
    }

    /**
     * Initialize question answers for a new submission
     */
    public static function initializeQuestionAnswers(ListeningSubmission $submission): void
    {
        $questions = $submission->test->testQuestions()->orderBy('question_order')->get();
        
        foreach ($questions as $question) {
            ListeningQuestionAnswer::create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'play_count' => 0
            ]);
        }
    }

    /**
     * Get test summary statistics
     */
    public static function getTestSummary(Test $test): array
    {
        $totalSubmissions = ListeningSubmission::where('test_id', $test->id)->count();
        $completedSubmissions = ListeningSubmission::where('test_id', $test->id)
            ->where('status', 'completed')->count();

        $averageScore = ListeningSubmission::where('test_id', $test->id)
            ->where('status', 'completed')
            ->avg('percentage') ?? 0;

        $averageTime = ListeningSubmission::where('test_id', $test->id)
            ->where('status', 'completed')
            ->avg('time_taken_seconds') ?? 0;

        return [
            'total_attempts' => $totalSubmissions,
            'completed_attempts' => $completedSubmissions,
            'completion_rate' => $totalSubmissions > 0 ? 
                ($completedSubmissions / $totalSubmissions) * 100 : 0,
            'average_score' => round($averageScore, 2),
            'average_time_seconds' => round($averageTime),
            'average_time_formatted' => static::formatTime(round($averageTime)),
            'total_questions' => $test->testQuestions()->count(),
            'has_audio_segments' => $test->passages()->whereHas('audioSegments')->exists(),
            'difficulty_level' => $test->difficulty_level ?? 'Not specified'
        ];
    }
}