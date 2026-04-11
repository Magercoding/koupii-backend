<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\ListeningSubmission;
use App\Models\User;
use App\Models\Test;
use App\Helpers\Listening\ListeningAnalyticsHelper;
use App\Models\ListeningQuestion;
use App\Models\ListeningQuestionAnswer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListeningAnalyticsService
{
    /**
     * Get comprehensive analytics for a listening task
     */
    public function getTaskAnalytics(ListeningTask $task): array
    {
        $submissions = ListeningSubmission::where('test_id', $task->test_id)
            ->with(['answers', 'student'])
            ->get();

        $completedSubmissions = $submissions->where('status', 'submitted');
        
        // Get class information from assignments
        $assignment = $task->assignments()->with('classroom')->first();
        $className = $assignment->classroom->name ?? 'No Class Assigned';

        $analytics = [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'class_name' => $className,
            'created_at' => $task->created_at->format('d M Y'),
            'total_submissions' => $submissions->count(),
            'completed_submissions' => $completedSubmissions->count(),
            'in_progress_submissions' => $submissions->where('status', 'in_progress')->count(),
            'completion_rate' => $submissions->count() > 0 ? 
                round(($completedSubmissions->count() / $submissions->count()) * 100, 2) : 0,
        ];

        if ($completedSubmissions->count() > 0) {
            $scores = $completedSubmissions->pluck('score')->filter();
            $completionTimes = $completedSubmissions->pluck('completion_time')->filter();
            
            $analytics = array_merge($analytics, [
                'average_score' => round($scores->avg(), 2),
                'median_score' => $this->calculateMedian($scores->toArray()),
                'highest_score' => $scores->max(),
                'lowest_score' => $scores->min(),
                'score_distribution' => $this->getScoreDistribution($scores->toArray()),
                'average_completion_time' => round($completionTimes->avg(), 2),
                'median_completion_time' => $this->calculateMedian($completionTimes->toArray()),
            ]);
        }

        // Question-level analytics
        $analytics['question_analytics'] = $this->getQuestionAnalytics($task);
        
        // Question type performance
        $analytics['question_type_performance'] = $this->analyzeQuestionTypePerformance($submissions);
        
        // Audio interaction analytics
        $analytics['audio_analytics'] = $this->getAudioInteractionSummary($task);
        
        // Difficulty analysis
        $analytics['difficulty_analysis'] = $this->analyzeDifficulty($completedSubmissions);

        // Leaderboard
        $analytics['leaderboard'] = $this->getLeaderboard($submissions);

        return $analytics;
    }

    /**
     * Get leaderboard for a set of submissions
     */
    private function getLeaderboard(Collection $submissions): array
    {
        return $submissions->groupBy('student_id')->map(function($studentWork) {
            // Get best attempt per student
            $bestAttempt = $studentWork->sortByDesc('score')->first();
            
            return [
                'id' => $bestAttempt->id,
                'student_name' => $bestAttempt->student->name ?? 'Unknown Student',
                'submission_date' => $bestAttempt->submitted_at ? $bestAttempt->submitted_at->format('d M Y') : ($bestAttempt->created_at ? $bestAttempt->created_at->format('d M Y') : '-'),
                'status' => $bestAttempt->status,
                'score' => (float)($bestAttempt->score ?? 0),
                'type' => 'listening',
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    /**
     * Get student performance analytics
     */
    public function getStudentPerformanceAnalytics(User $student, array $filters = []): array
    {
        $query = ListeningSubmission::where('student_id', $student->id)
            ->with(['test.listeningTask', 'answers']);

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $submissions = $query->get();
        $completedSubmissions = $submissions->where('status', 'submitted');

        $analytics = [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'total_submissions' => $submissions->count(),
            'completed_submissions' => $completedSubmissions->count(),
            'completion_rate' => $submissions->count() > 0 ? 
                round(($completedSubmissions->count() / $submissions->count()) * 100, 2) : 0,
        ];

        if ($completedSubmissions->count() > 0) {
            $scores = $completedSubmissions->pluck('score')->filter();
            
            $analytics = array_merge($analytics, [
                'average_score' => round($scores->avg(), 2),
                'highest_score' => $scores->max(),
                'lowest_score' => $scores->min(),
                'score_trend' => $this->calculateScoreTrend($completedSubmissions),
                'total_study_time' => $completedSubmissions->sum('completion_time'),
                'average_completion_time' => round($completedSubmissions->avg('completion_time'), 2),
                'improvement_rate' => $this->calculateImprovementRate($completedSubmissions),
            ]);
        }

        // Question type performance
        $analytics['question_type_performance'] = $this->getStudentQuestionTypePerformance($student, $filters);
        
        // Audio interaction patterns
        $analytics['audio_interaction_patterns'] = $this->getStudentAudioPatterns($student, $filters);
        
        // Strengths and weaknesses
        $analytics['strengths'] = $this->identifyStudentStrengths($student, $completedSubmissions);
        $analytics['areas_for_improvement'] = $this->identifyImprovementAreas($student, $completedSubmissions);

        return $analytics;
    }

    /**
     * Get question type analytics across all submissions
     */
    public function getQuestionTypeAnalytics(array $filters = []): array
    {
        $query = ListeningSubmission::with(['answers.question', 'test']);

        // Apply filters
        if (!empty($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $submissions = $query->get();
        
        return $this->analyzeQuestionTypePerformance($submissions);
    }

    /**
     * Get audio interaction analytics
     */
    public function getAudioInteractionAnalytics(array $filters = []): array
    {
        $query = DB::table('listening_audio_logs')
            ->join('listening_submissions', 'listening_audio_logs.submission_id', '=', 'listening_submissions.id')
            ->join('listening_audio_segments', 'listening_audio_logs.audio_segment_id', '=', 'listening_audio_segments.id');

        // Apply filters
        if (!empty($filters['task_id'])) {
            $query->join('listening_tasks', 'listening_submissions.test_id', '=', 'listening_tasks.test_id')
                  ->where('listening_tasks.id', $filters['task_id']);
        }
        
        if (!empty($filters['student_id'])) {
            $query->where('listening_submissions.student_id', $filters['student_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('listening_audio_logs.played_at', '>=', Carbon::parse($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('listening_audio_logs.played_at', '<=', Carbon::parse($filters['date_to']));
        }

        $audioLogs = $query->get();
        
        return $this->analyzeAudioInteractions($audioLogs);
    }

    /**
     * Get progress analytics for student
     */
    public function getProgressAnalytics(User $student, string $timeframe = '30days'): array
    {
        $startDate = $this->getStartDateForTimeframe($timeframe);
        
        $submissions = ListeningSubmission::where('student_id', $student->id)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'submitted')
            ->orderBy('created_at')
            ->get();

        return [
            'timeframe' => $timeframe,
            'progress_trend' => $this->calculateProgressTrend($submissions),
            'skill_development' => $this->analyzeSkillDevelopment($submissions),
            'consistency_metrics' => $this->calculateConsistencyMetrics($submissions),
            'goal_progress' => $this->calculateGoalProgress($student, $submissions),
            'recommendations' => $this->generateProgressRecommendations($student, $submissions)
        ];
    }

    /**
     * Get comparative analytics between students
     */
    public function getComparativeAnalytics(array $request): array
    {
        $studentIds = $request['student_ids'];
        $metric = $request['metric'] ?? 'accuracy';
        
        $students = User::whereIn('id', $studentIds)->get();
        $comparativeData = [];
        
        foreach ($students as $student) {
            /** @var User $student */
            $analytics = $this->getStudentPerformanceAnalytics($student, $request);
            $comparativeData[] = [
                'student' => $student,
                'metrics' => $analytics,
                'comparison_score' => $this->getMetricValue($analytics, $metric)
            ];
        }
        
        // Sort by comparison metric
        usort($comparativeData, function($a, $b) {
            return $b['comparison_score'] <=> $a['comparison_score'];
        });
        
        return [
            'students' => $comparativeData,
            'metric' => $metric,
            'summary' => $this->generateComparativeSummary($comparativeData, $metric)
        ];
    }

    /**
     * Generate comprehensive report
     */
    public function generateReport(array $request): array
    {
        $reportType = $request['report_type'];
        
        switch ($reportType) {
            case 'task_performance':
                return $this->generateTaskPerformanceReport($request);
            case 'student_progress':
                return $this->generateStudentProgressReport($request);
            case 'class_overview':
                return $this->generateClassOverviewReport($request);
            case 'question_analysis':
                return $this->generateQuestionAnalysisReport($request);
            default:
                throw new \InvalidArgumentException('Invalid report type: ' . $reportType);
        }
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData(array $filters = []): array
    {
        $user = auth()->user();
        
        // If the user is a student, return student-specific dashboard data
        if ($user && $user->role === 'student') {
            return $this->getStudentDashboardData($user, $filters);
        }

        $timeframe = $filters['timeframe'] ?? 'week';
        $month = $filters['month'] ?? null;
        
        $startDate = $this->getStartDateForTimeframe($timeframe);
        $endDate = Carbon::now();

        if ($month) {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        }

        $teacherId = $filters['teacher_id'] ?? ($user ? $user->id : null);
        $classId = $filters['class_id'] ?? null;
        
        // Base query for tasks created by this teacher
        $tasksQuery = ListeningTask::where('created_by', $teacherId);
        
        if ($classId && $classId !== 'all') {
            $tasksQuery->whereHas('assignments', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }
        
        $tasks = $tasksQuery->get();
        $taskIds = $tasks->pluck('id');

        // Fetch submissions for these tasks
        $submissionsQuery = ListeningSubmission::whereIn('listening_task_id', $taskIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['student', 'task']);

        if ($classId && $classId !== 'all') {
            $submissionsQuery->where(function($q) use ($classId) {
                // Either linked via assignment directly
                $q->whereHas('assignment', function($subQ) use ($classId) {
                    $subQ->where('class_id', $classId);
                })
                // Or if it's a general task but student is in the class
                ->orWhereHas('student.studentClasses', function($subQ) use ($classId) {
                    $subQ->where('class_id', $classId);
                });
            });
        }
        
        $submissions = $submissionsQuery->get();
        $bestSubmissions = $this->getBestSubmissions($submissions);

        // Get all classes taught by this teacher for the filter
        $classes = DB::table('classes')
            ->where('teacher_id', $teacherId)
            ->select('id', 'name')
            ->get();
        
        return [
            'overview' => $this->getDashboardOverview($bestSubmissions, $submissions),
            'recent_activity' => $this->getRecentActivity($submissions->take(10)),
            'performance_trends' => $this->getPerformanceTrends($bestSubmissions, $timeframe),
            'top_performers' => $this->getTopPerformers($bestSubmissions),
            'struggling_students' => $this->getStrugglingStudents($bestSubmissions),
            'popular_tasks' => $this->getPopularTasks($submissions),
            'question_type_insights' => $this->getQuestionTypeInsights($bestSubmissions),
            'classes' => $classes,
            'total_tasks' => $tasks->count()
        ];
    }

    /**
     * Get dashboard data for a student
     */
    private function getStudentDashboardData(User $student, array $filters = []): array
    {
        $timeframe = $filters['timeframe'] ?? 'all';
        $startDate = $this->getStartDateForTimeframe($timeframe);
        
        // Fetch all submitted submissions for this student
        $submissions = ListeningSubmission::where('student_id', $student->id)
            ->where('status', 'submitted')
            ->when($timeframe !== 'all', function($q) use ($startDate) {
                return $q->where('submitted_at', '>=', $startDate);
            })
            ->with(['task'])
            ->get();

        // Get best submission per task
        $bestSubmissions = $this->getBestSubmissions($submissions);

        // Calculate stats
        $totalCompleted = $bestSubmissions->count();
        $averageScore = $totalCompleted > 0 ? round($bestSubmissions->avg('percentage'), 1) : 0;
        
        // Total time spent (sum of time_taken_seconds across all attempts)
        $totalTimeSeconds = $submissions->sum('time_taken_seconds') ?? 0;
        $timeSpentMinutes = round($totalTimeSeconds / 60, 0);

        return [
            'tasks_completed' => $totalCompleted,
            'time_spent_minutes' => $timeSpentMinutes,
            'average_score' => $averageScore,
            'performance_by_month' => $this->getPerformanceByMonth($bestSubmissions),
            'performance_by_section' => $this->getPerformanceBySection($submissions, $student),
            'question_type_accuracy' => $this->getQuestionTypeAccuracy($bestSubmissions),
        ];
    }

    /**
     * Export analytics data
     */
    public function exportData(array $request): array
    {
        $exportType = $request['export_type'];
        $dataType = $request['data_type'];
        $filters = $request['filters'] ?? [];
        
        switch ($dataType) {
            case 'submissions':
                $data = $this->exportSubmissionsData($filters);
                break;
            case 'audio_logs':
                $data = $this->exportAudioLogsData($filters);
                break;
            case 'performance_metrics':
                $data = $this->exportPerformanceMetrics($filters);
                break;
            default:
                throw new \InvalidArgumentException('Invalid data type: ' . $dataType);
        }
        
        return $this->formatExportData($data, $exportType);
    }

    /**
     * Helper method to calculate median
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);
        
        if ($count % 2) {
            return $values[$middle];
        }
        
        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    /**
     * Get score distribution
     */
    private function getScoreDistribution(array $scores): array
    {
        $ranges = [
            '0-20' => 0, '21-40' => 0, '41-60' => 0, 
            '61-80' => 0, '81-100' => 0
        ];
        
        foreach ($scores as $score) {
            if ($score <= 20) $ranges['0-20']++;
            elseif ($score <= 40) $ranges['21-40']++;
            elseif ($score <= 60) $ranges['41-60']++;
            elseif ($score <= 80) $ranges['61-80']++;
            else $ranges['81-100']++;
        }
        
        return $ranges;
    }

    /**
     * Get question analytics for task
     */
    private function getQuestionAnalytics(ListeningTask $task): array
    {
        $submissions = $task->submissions()->whereNotNull('submitted_at')->with('answers')->get();
        $questions = $task->questions;
        $stats = [];

        foreach ($questions as $question) {
            $answers = ListeningQuestionAnswer::where('question_id', $question->id)
                ->whereIn('submission_id', $submissions->pluck('id'))
                ->get();
            
            $correct = $answers->where('is_correct', true)->count();
            $total = $answers->count();
            
            $stats[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                'total_responses' => $total
            ];
        }

        return $stats;
    }

    /**
     * Get audio interaction summary
     */
    private function getAudioInteractionSummary(ListeningTask $task): array
    {
        $submissions = $task->submissions()->whereNotNull('submitted_at')->get();
        $totalPlays = 0;
        $interactionPatterns = [];

        foreach ($submissions as $sub) {
            $counts = $sub->audio_play_counts ?? [];
            foreach ($counts as $segmentId => $count) {
                $totalPlays += $count;
                $interactionPatterns[$segmentId] = ($interactionPatterns[$segmentId] ?? 0) + $count;
            }
        }

        return [
            'total_plays' => $totalPlays,
            'average_plays_per_submission' => $submissions->count() > 0 ? round($totalPlays / $submissions->count(), 1) : 0,
            'most_played_segments' => $interactionPatterns,
            'audio_interaction_patterns' => $interactionPatterns
        ];
    }

    /**
     * Analyze difficulty
     */
    private function analyzeDifficulty(Collection $submissions): array
    {
        $avgScore = $submissions->avg('percentage') ?? 0;
        $difficulty = 'medium';
        
        if ($avgScore > 80) $difficulty = 'easy';
        elseif ($avgScore < 40) $difficulty = 'hard';

        return [
            'overall_difficulty' => $difficulty,
            'average_percentage' => round($avgScore, 1),
            'difficulty_distribution' => $this->getScoreDistribution($submissions->pluck('percentage')->toArray()),
            'suggested_adjustments' => []
        ];
    }

    /**
     * Calculate score trend
     */
    private function calculateScoreTrend(Collection $submissions): string
    {
        if ($submissions->count() < 2) return 'insufficient_data';
        
        $recentScores = $submissions->sortBy('created_at')->takeLast(5)->pluck('score');
        $earlierScores = $submissions->sortBy('created_at')->take(5)->pluck('score');
        
        $recentAvg = $recentScores->avg();
        $earlierAvg = $earlierScores->avg();
        
        $difference = $recentAvg - $earlierAvg;
        
        if ($difference > 5) return 'improving';
        if ($difference < -5) return 'declining';
        return 'stable';
    }

    /**
     * Calculate improvement rate
     */
    private function calculateImprovementRate(Collection $submissions): float
    {
        if ($submissions->count() < 2) return 0;
        
        $sorted = $submissions->sortBy('created_at');
        $first = $sorted->first()->score;
        $last = $sorted->last()->score;
        
        return $first > 0 ? round((($last - $first) / $first) * 100, 2) : 0;
    }

    /**
     * Get start date for timeframe
     */
    private function getStartDateForTimeframe(string $timeframe): Carbon
    {
        switch ($timeframe) {
            case '7days': return Carbon::now()->subDays(7);
            case '30days': return Carbon::now()->subDays(30);
            case '90days': return Carbon::now()->subDays(90);
            case '1year': return Carbon::now()->subYear();
            default: return Carbon::now()->subDays(30);
        }
    }

    private function getStudentQuestionTypePerformance(User $student, array $filters): array
    {
        $submissions = ListeningSubmission::where('student_id', $student->id)
            ->where('status', 'submitted')
            ->with('answers.question')
            ->get();

        $types = [];
        foreach ($submissions as $sub) {
            foreach ($sub->answers as $answer) {
                $type = $answer->question->question_type ?? 'unknown';
                if (!isset($types[$type])) {
                    $types[$type] = ['total' => 0, 'correct' => 0];
                }
                $types[$type]['total']++;
                if ($answer->is_correct) {
                    $types[$type]['correct']++;
                }
            }
        }

        $result = [];
        foreach ($types as $type => $stats) {
            $result[] = [
                'type' => $type,
                'name' => ListeningQuestion::QUESTION_TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)),
                'accuracy' => round(($stats['correct'] / $stats['total']) * 100, 1),
                'total_questions' => $stats['total']
            ];
        }

        return $result;
    }

    private function getStudentAudioPatterns(User $student, array $filters): array
    {
        $submissions = ListeningSubmission::where('student_id', $student->id)->get();
        $totalPlays = 0;
        
        foreach ($submissions as $sub) {
            $counts = $sub->audio_play_counts ?? [];
            foreach ($counts as $count) {
                $totalPlays += $count;
            }
        }

        return [
            'total_plays' => $totalPlays,
            'average_plays_per_task' => $submissions->count() > 0 ? round($totalPlays / $submissions->count(), 1) : 0
        ];
    }

    private function identifyStudentStrengths(User $student, Collection $submissions): array
    {
        $types = $this->getStudentQuestionTypePerformance($student, []);
        if (empty($types)) return [];
        usort($types, fn($a, $b) => $b['accuracy'] <=> $a['accuracy']);
        return array_slice($types, 0, 2);
    }

    private function identifyImprovementAreas(User $student, Collection $submissions): array
    {
        $types = $this->getStudentQuestionTypePerformance($student, []);
        if (empty($types)) return [];
        usort($types, fn($a, $b) => $a['accuracy'] <=> $b['accuracy']);
        return array_slice($types, 0, 2);
    }

    private function getPerformanceByMonth(Collection $bestSubmissions): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $months[$monthKey] = [
                'label' => $date->format('M'),
                'total' => 0,
                'count' => 0
            ];
        }

        foreach ($bestSubmissions as $sub) {
            $date = $sub->submitted_at ?: $sub->created_at;
            $monthKey = $date->format('Y-m');
            if (isset($months[$monthKey])) {
                $months[$monthKey]['total'] += $sub->percentage;
                $months[$monthKey]['count']++;
            }
        }

        return array_values(array_map(fn($m) => [
            'label' => $m['label'],
            'average' => $m['count'] > 0 ? round($m['total'] / $m['count'], 1) : 0
        ], $months));
    }

    private function getPerformanceBySection(Collection $allSubmissions, User $student): array
    {
        $weeks = [];
        for ($i = 3; $i >= 0; $i--) {
            $weeks[$i] = [
                'week' => "Week " . (4 - $i),
                'multipleChoice' => 0,
                'identifyingInformation' => 0,
                'matchingHeading' => 0,
                'sentenceCompletion' => 0,
                'paragraphCompletion' => 0,
                'counts' => [
                    'multipleChoice' => 0,
                    'identifyingInformation' => 0,
                    'matchingHeading' => 0,
                    'sentenceCompletion' => 0,
                    'paragraphCompletion' => 0
                ]
            ];
        }

        $typeMapping = [
            'multiple_choice' => 'multipleChoice',
            'multiple_choice_multiple_answer' => 'multipleChoice',
            'identifying_information' => 'identifyingInformation',
            'matching_headings' => 'matchingHeading',
            'sentence_completion' => 'sentenceCompletion',
            'summary_completion' => 'paragraphCompletion',
            'note_completion' => 'paragraphCompletion',
            'table_completion' => 'paragraphCompletion',
            'flow_chart_completion' => 'paragraphCompletion',
            'diagram_labeling' => 'paragraphCompletion',
        ];

        foreach ($allSubmissions as $sub) {
            $subDate = $sub->submitted_at ?: $sub->created_at;
            $diff = now()->diffInWeeks($subDate);
            if ($diff <= 3) {
                foreach ($sub->answers as $ans) {
                    $key = $typeMapping[$ans->question->question_type ?? ''] ?? null;
                    if ($key) {
                        $weeks[$diff][$key] += $ans->is_correct ? 100 : 0;
                        $weeks[$diff]['counts'][$key]++;
                    }
                }
            }
        }

        return array_values(array_map(function($w) {
            foreach (['multipleChoice', 'identifyingInformation', 'matchingHeading', 'sentenceCompletion', 'paragraphCompletion'] as $k) {
                $w[$k] = $w['counts'][$k] > 0 ? round($w[$k] / $w['counts'][$k], 1) : 0;
            }
            unset($w['counts']);
            return $w;
        }, array_reverse($weeks)));
    }

    private function getQuestionTypeAccuracy(Collection $bestSubmissions): array
    {
        $types = [];
        foreach ($bestSubmissions as $sub) {
            foreach ($sub->answers as $ans) {
                $type = $ans->question->question_type ?? 'unknown';
                if (!isset($types[$type])) $types[$type] = ['c' => 0, 't' => 0];
                $types[$type]['t']++;
                if ($ans->is_correct) $types[$type]['c']++;
            }
        }

        $res = [];
        foreach ($types as $type => $stats) {
            $res[] = [
                'test_name' => ListeningQuestion::QUESTION_TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)),
                'accuracy' => round(($stats['c'] / $stats['t']) * 100, 1)
            ];
        }
        return $res;
    }

    private function getBestSubmissions(Collection $submissions): Collection
    {
        return $submissions->groupBy(function ($sub) {
            return $sub->student_id . '_' . $sub->listening_task_id;
        })->map(function ($studentTaskWork) {
            return $studentTaskWork->filter(function($sub) {
                return in_array($sub->status, [ListeningSubmission::STATUS_SUBMITTED, ListeningSubmission::STATUS_REVIEWED, ListeningSubmission::STATUS_DONE]);
            })->sortByDesc('total_score')->first();
        })->filter()->values();
    }

    private function getDashboardOverview(Collection $bestSubmissions, Collection $allSubmissions): array
    {
        $avgScore = $bestSubmissions->avg('percentage') ?? 0;
        
        return [
            'tasks_completed' => $bestSubmissions->count(),
            'total_submissions' => $allSubmissions->count(),
            'average_score' => round($avgScore, 1),
            'active_students' => $allSubmissions->pluck('student_id')->unique()->count()
        ];
    }
    
    private function getRecentActivity(Collection $submissions): array
    {
        return $submissions->map(function($sub) {
            return [
                'id' => $sub->id,
                'student_name' => $sub->student->name ?? 'Unknown Student',
                'task_title' => $sub->task->title ?? 'Untitled Task',
                'score' => (float)$sub->percentage,
                'total_score' => (float)$sub->total_score,
                'submitted_at' => $sub->created_at->diffForHumans()
            ];
        })->toArray();
    }
    
    private function getPerformanceTrends(Collection $bestSubmissions, string $timeframe): array
    {
        $trends = $bestSubmissions->groupBy(function($sub) {
            return $sub->created_at->format('Y-m');
        })->map(function($group) {
            return round($group->avg('percentage'), 1);
        });

        $result = [];
        foreach ($trends as $month => $score) {
            $result[] = [
                'month' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                'score' => $score
            ];
        }
        
        return array_values($result);
    }
    
    private function getTopPerformers(Collection $bestSubmissions): array
    {
        return $bestSubmissions->groupBy('student_id')->map(function($studentWork) {
            return [
                'student_name' => $studentWork->first()->student->name ?? 'Unknown',
                'average_score' => round($studentWork->avg('percentage'), 1),
                'tasks_completed' => $studentWork->count()
            ];
        })->sortByDesc('average_score')->take(5)->values()->toArray();
    }
    
    private function getStrugglingStudents(Collection $bestSubmissions): array
    {
        return $bestSubmissions->groupBy('student_id')->map(function($studentWork) {
            return [
                'student_name' => $studentWork->first()->student->name ?? 'Unknown',
                'average_score' => round($studentWork->avg('percentage'), 1),
                'tasks_completed' => $studentWork->count()
            ];
        })->sortBy('average_score')->take(5)->values()->toArray();
    }
    
    private function getPopularTasks(Collection $submissions): array
    {
        return $submissions->groupBy('listening_task_id')->map(function($taskSubmissions) {
            return [
                'task_title' => $taskSubmissions->first()->task->title ?? 'Untitled',
                'submission_count' => $taskSubmissions->count(),
                'average_score' => round($taskSubmissions->avg('percentage'), 1)
            ];
        })->sortByDesc('submission_count')->take(5)->values()->toArray();
    }
    
    private function getQuestionTypeInsights(Collection $bestSubmissions): array
    {
        $typeStats = [];
        foreach ($bestSubmissions as $sub) {
            foreach ($sub->answers as $answer) {
                $type = $answer->question->question_type ?? 'unknown';
                if (!isset($typeStats[$type])) {
                    $typeStats[$type] = ['correct' => 0, 'total' => 0];
                }
                $typeStats[$type]['total']++;
                if ($answer->is_correct) {
                    $typeStats[$type]['correct']++;
                }
            }
        }

        $mastery = [];
        foreach ($typeStats as $type => $stats) {
            $accuracy = $stats['total'] > 0 ? round(($stats['correct'] / $stats['total']) * 100, 0) : 0;
            $mastery[] = [
                'id' => $type,
                'name' => ListeningQuestion::QUESTION_TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)),
                'score' => (int)$accuracy
            ];
        }

        usort($mastery, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return [
            'mastery' => $mastery,
            'best_type' => count($mastery) > 0 ? $mastery[0] : null,
            'weakest_type' => count($mastery) > 0 ? end($mastery) : null
        ];
    }

    private function analyzeQuestionTypePerformance(Collection $submissions): array
    {
        $stats = [];
        foreach ($submissions as $sub) {
            foreach ($sub->answers as $ans) {
                $type = $ans->question->question_type ?? 'unknown';
                if (!isset($stats[$type])) {
                    $stats[$type] = ['correct' => 0, 'total' => 0];
                }
                $stats[$type]['total']++;
                if ($ans->is_correct) $stats[$type]['correct']++;
            }
        }
        
        $result = [];
        foreach ($stats as $type => $data) {
            $result[] = [
                'type' => $type,
                'name' => ListeningQuestion::QUESTION_TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)),
                'accuracy' => $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0,
                'total_questions' => $data['total']
            ];
        }
        return $result;
    }

    private function analyzeAudioInteractions(Collection $logs): array
    {
        return [
            'total_interactions' => $logs->count(),
            'unique_segments' => $logs->pluck('audio_segment_id')->unique()->count(),
            'action_distribution' => $logs->groupBy('action')->map->count(),
            'timeline' => $logs->groupBy(fn($l) => Carbon::parse($l->played_at)->format('Y-m-d H:00'))->map->count()
        ];
    }

    private function calculateProgressTrend(Collection $submissions): array
    {
        return $submissions->sortBy('created_at')->map(fn($s) => [
            'date' => $s->created_at->format('Y-m-d'),
            'score' => $s->percentage
        ])->values()->toArray();
    }

    private function analyzeSkillDevelopment(Collection $submissions): array
    {
        $types = $this->analyzeQuestionTypePerformance($submissions);
        return array_map(fn($t) => [
            'skill' => $t['name'],
            'level' => $t['accuracy'] > 80 ? 'Advanced' : ($t['accuracy'] > 50 ? 'Intermediate' : 'Beginner'),
            'score' => $t['accuracy']
        ], $types);
    }

    private function calculateConsistencyMetrics(Collection $submissions): array
    {
        $daysDiff = $submissions->count() > 1 ? 
            $submissions->first()->created_at->diffInDays($submissions->last()->created_at) : 0;
            
        return [
            'frequency' => $daysDiff > 0 ? round($submissions->count() / $daysDiff, 2) : $submissions->count(),
            'average_gap_days' => $submissions->count() > 1 ? round($daysDiff / ($submissions->count() - 1), 1) : 0
        ];
    }

    private function calculateGoalProgress(User $student, Collection $submissions): array
    {
        $target = 80; // Default target
        $current = $submissions->avg('percentage') ?? 0;
        return [
            'target_score' => $target,
            'current_average' => round($current, 1),
            'percent_to_goal' => $target > 0 ? min(100, round(($current / $target) * 100, 1)) : 0
        ];
    }

    private function generateProgressRecommendations(User $student, Collection $submissions): array
    {
        $weaknesses = $this->identifyImprovementAreas($student, $submissions);
        $recs = [];
        foreach ($weaknesses as $w) {
            $recs[] = "Focus more on " . $w['name'] . " tasks to improve your overall score.";
        }
        if (empty($recs)) $recs[] = "Continue practicing varied tasks to maintain your performance.";
        return $recs;
    }

    private function generateComparativeSummary(array $comparativeData, string $metric): array
    {
        $scores = array_column($comparativeData, 'comparison_score');
        return [
            'average' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0,
            'highest' => count($scores) > 0 ? max($scores) : 0,
            'lowest' => count($scores) > 0 ? min($scores) : 0
        ];
    }

    private function generateTaskPerformanceReport(array $request): array { return ['type' => 'task_performance', 'generated_at' => now()]; }
    private function generateStudentProgressReport(array $request): array { return ['type' => 'student_progress', 'generated_at' => now()]; }
    private function generateClassOverviewReport(array $request): array { return ['type' => 'class_overview', 'generated_at' => now()]; }
    private function generateQuestionAnalysisReport(array $request): array { return ['type' => 'question_analysis', 'generated_at' => now()]; }

    private function getMetricValue(array $analytics, string $metric): float
    {
        return (float)($analytics[$metric] ?? 0);
    }

    private function exportSubmissionsData(array $filters): Collection
    {
        return ListeningSubmission::whereNotNull('submitted_at')->get();
    }

    private function exportAudioLogsData(array $filters): Collection
    {
        return DB::table('listening_audio_logs')->get();
    }

    private function exportPerformanceMetrics(array $filters): Collection
    {
        return ListeningSubmission::where('status', 'submitted')->get();
    }

    private function formatExportData(Collection $data, string $exportType): array
    {
        return [
            'format' => $exportType,
            'data' => $data->toArray(),
            'count' => $data->count(),
            'generated_at' => now()
        ];
    }
}