<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningAudioLog;
use App\Models\ListeningQuestionAnswer;
use App\Models\ListeningSubmission;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\User;
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
        $ongoingSubmission = ListeningSubmission::where('test_id', $test->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($ongoingSubmission) {
            return $ongoingSubmission;
        }

        // Check repetition limits
        $attemptNumber = ListeningSubmission::where('test_id', $test->id)
            ->where('student_id', $student->id)
            ->count() + 1;

        if ($test->max_repetition_count && $attemptNumber > $test->max_repetition_count) {
            throw new \Exception('Maximum attempt limit reached for this test');
        }

        return DB::transaction(function () use ($test, $student, $attemptNumber) {
            $submission = ListeningSubmission::create([
                'test_id' => $test->id,
                'student_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
                'audio_play_counts' => []
            ]);

            $questions = $test->testQuestions()->orderBy('question_order')->get();
            
            foreach ($questions as $question) {
                ListeningQuestionAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'play_count' => 0
                ]);
            }

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
     * Complete a listening test and calculate final score
     */
    public function completeTest(ListeningSubmission $submission): ListeningSubmission
    {
        if ($submission->status === 'completed') {
            return $submission;
        }

        return DB::transaction(function () use ($submission) {
            // Calculate time taken
            $timeTaken = now()->diffInSeconds($submission->started_at);

            // Calculate final score
            $submission->update([
                'status' => 'completed',
                'submitted_at' => now(),
                'time_taken_seconds' => $timeTaken
            ]);

            $submission->calculateScore();

            // Process vocabulary discoveries from audio content
            $this->processVocabularyDiscoveries($submission);

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
                    'correct_answer' => $this->getCorrectAnswerText($answer->question),
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
     * Get correct answer text for display
     */
    private function getCorrectAnswerText(TestQuestion $question): string
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'single_correct':
            case 'true_false':
                $correctOption = $question->options()->where('is_correct', true)->first();
                return $correctOption ? $correctOption->option_text : 'No correct answer set';

            case 'multiple_correct':
                $correctOptions = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctOptions->implode(', ');

            case 'fill_in_the_blank':
            case 'short_answer':
            case 'listening_comprehension':
            case 'audio_dictation':
                $correctAnswers = $question->options()->where('is_correct', true)->pluck('option_text');
                return $correctAnswers->implode(' / ');

            case 'matching':
                $matches = $question->questionBreakdowns()
                    ->with('questionGroup')
                    ->get()
                    ->map(function ($breakdown) {
                        return $breakdown->breakdown_text . ' -> ' . $breakdown->questionGroup->correct_answer;
                    });
                return $matches->implode('; ');

            case 'ordering':
            case 'drag_drop':
                $correctOrder = $question->options()
                    ->orderBy('display_order')
                    ->pluck('option_text');
                return $correctOrder->implode(' → ');

            default:
                return 'Manual evaluation required';
        }
    }

    /**
     * Process vocabulary discoveries from audio content
     */
    private function processVocabularyDiscoveries(ListeningSubmission $submission): void
    {
        // This method would analyze the audio content and context
        // to automatically discover vocabulary words
        // For now, it's a placeholder for future implementation
        
        // Implementation could include:
        // - Text analysis of transcripts
        // - Integration with vocabulary APIs
        // - Machine learning-based word difficulty assessment
        // - Automatic definition and pronunciation generation
    }

    /**
     * Get student's listening test history
     */
    public function getStudentHistory(User $student, array $filters = []): Collection
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
    public function getPerformanceAnalytics(User $student): array
    {
        $submissions = $this->getStudentHistory($student);

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