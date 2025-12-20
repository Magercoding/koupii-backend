<?php

namespace App\Helpers\Listening;

use App\Models\ListeningSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListeningAnalyticsHelper
{
    /**
     * Get detailed test results with analytics
     */
    public static function getDetailedResult(ListeningSubmission $submission): array
    {
        $answers = $submission->answers()->with(['question', 'selectedOption'])->get();
        $vocabularyDiscovered = $submission->vocabularyDiscoveries()->count();
        $audioLogs = $submission->audioLogs()->get();

        // Calculate audio analytics
        $totalAudioPlays = $audioLogs->where('action_type', 'play')->count();
        $totalListeningTime = $audioLogs->sum('duration_listened');
        $averagePlaybackSpeed = $audioLogs->where('playback_speed', '>', 0)->avg('playback_speed') ?? 1.0;

        // Calculate question analytics
        $questionAnalytics = [];
        foreach ($answers as $answer) {
            $questionLogs = $audioLogs->where('question_id', $answer->question_id);
            
            $questionAnalytics[$answer->question_id] = [
                'play_count' => $questionLogs->where('action_type', 'play')->count(),
                'total_listening_time' => $questionLogs->sum('duration_listened'),
                'replay_count' => $questionLogs->where('action_type', 'replay')->count(),
                'seek_count' => $questionLogs->where('action_type', 'seek')->count()
            ];
        }

        return [
            'submission' => $submission,
            'answers' => $answers->map(function ($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text,
                    'question_type' => $answer->question->question_type,
                    'selected_answer' => $answer->formatted_answer,
                    'correct_answer' => ListeningQuestionHelper::getCorrectAnswerText($answer->question),
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'explanation' => $answer->answer_explanation,
                    'time_spent' => $answer->time_spent_seconds,
                    'play_count' => $answer->play_count
                ];
            }),
            'vocabulary_discovered' => $vocabularyDiscovered,
            'audio_analytics' => [
                'total_plays' => $totalAudioPlays,
                'total_listening_time' => $totalListeningTime,
                'average_playback_speed' => round($averagePlaybackSpeed, 2),
                'question_breakdown' => $questionAnalytics
            ],
            'performance_metrics' => [
                'accuracy_rate' => $submission->percentage,
                'completion_time' => $submission->time_taken_seconds,
                'grade' => $submission->grade,
                'can_retake' => $submission->canRetake()
            ]
        ];
    }

    /**
     * Get student's listening test history
     */
    public static function getStudentHistory(User $student, array $filters = []): Collection
    {
        $query = ListeningSubmission::where('student_id', $student->id)
            ->with(['test'])
            ->where('status', 'completed');

        if (isset($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    /**
     * Get listening performance analytics for student
     */
    public static function getPerformanceAnalytics(User $student): array
    {
        $submissions = static::getStudentHistory($student);

        if ($submissions->isEmpty()) {
            return [
                'total_tests' => 0,
                'average_score' => 0,
                'improvement_rate' => 0,
                'total_listening_time' => 0,
                'vocabulary_discovered' => 0
            ];
        }

        $totalTests = $submissions->count();
        $averageScore = $submissions->avg('percentage');
        $totalListeningTime = $submissions->sum('time_taken_seconds');

        // Calculate improvement rate (comparing first 3 and last 3 tests)
        $improvementRate = 0;
        if ($totalTests >= 6) {
            $firstThree = $submissions->take(-3)->avg('percentage');
            $lastThree = $submissions->take(3)->avg('percentage');
            $improvementRate = $lastThree - $firstThree;
        }

        $vocabularyDiscovered = $submissions->sum(function ($submission) {
            return $submission->vocabularyDiscoveries()->count();
        });

        return [
            'total_tests' => $totalTests,
            'average_score' => round($averageScore, 2),
            'improvement_rate' => round($improvementRate, 2),
            'total_listening_time' => $totalListeningTime,
            'vocabulary_discovered' => $vocabularyDiscovered,
            'recent_performance' => $submissions->take(5)->map(function ($submission) {
                return [
                    'test_name' => $submission->test->title,
                    'score' => $submission->percentage,
                    'date' => $submission->submitted_at->format('Y-m-d')
                ];
            })
        ];
    }
}