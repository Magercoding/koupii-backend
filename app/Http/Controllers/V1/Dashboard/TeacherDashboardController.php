<?php

namespace App\Http\Controllers\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherDashboardController extends Controller
{
    public function statistics(Request $request)
    {
        $userId = auth()->id();

        // 1. Total Classes
        $totalClasses = Classes::where('teacher_id', $userId)->count();

        // 2. Total Students (Unique across all teacher's classes or specific class)
        $totalStudents = DB::table('class_enrollments')
            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
            ->where('classes.teacher_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('class_enrollments.class_id', $request->class_id);
            })
            ->distinct('class_enrollments.student_id')
            ->count('class_enrollments.student_id');

        // 3. Submissions / Tasks Completed
        $readingBase = DB::table('reading_submissions')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
            ->where(function ($query) use ($userId) {
                $query->where('tests.creator_id', $userId)
                    ->orWhere('reading_tasks.created_by', $userId);
            })
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('reading_submissions.submitted_at');

        $listeningBase = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at');

        $writingBase = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at');

        $speakingBase = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at');

        $tasksCompleted = (clone $readingBase)->count() 
                        + (clone $listeningBase)->count() 
                        + (clone $writingBase)->count()
                        + (clone $speakingBase)->count();

        // 4. Time Spent
        $totalSeconds = (clone $readingBase)->sum('reading_submissions.time_taken_seconds') 
                      + (clone $listeningBase)->sum('listening_submissions.time_taken_seconds') 
                      + (clone $writingBase)->sum('writing_submissions.time_taken_seconds')
                      + (clone $speakingBase)->sum('speaking_submissions.total_time_seconds');
        
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpentFormatted = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpentFormatted = "0h";

        // 5. Average Score
        $readingPercentageSum = (clone $readingBase)->sum('reading_submissions.percentage');
        $listeningPercentageSum = (clone $listeningBase)->sum('listening_submissions.percentage');

        $writingScoreData = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->join('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at')
            ->selectRaw('SUM(writing_reviews.score) as total_score, COUNT(writing_reviews.id) as review_count')
            ->first();

        $writingScoreSum = $writingScoreData ? ($writingScoreData->total_score ?? 0) : 0;
        $writingReviewCount = $writingScoreData ? ($writingScoreData->review_count ?? 0) : 0;

        $speakingScoreData = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->selectRaw('SUM(speaking_reviews.total_score) as total_score, COUNT(speaking_reviews.id) as review_count')
            ->first();

        $speakingScoreSum = $speakingScoreData ? ($speakingScoreData->total_score ?? 0) : 0;
        $speakingReviewCount = $speakingScoreData ? ($speakingScoreData->review_count ?? 0) : 0;

        $scoredCount = ((clone $readingBase)->count() + (clone $listeningBase)->count() + $writingReviewCount + $speakingReviewCount);
        $totalScore = $readingPercentageSum + $listeningPercentageSum + $writingScoreSum + $speakingScoreSum;

        $averageScore = $scoredCount > 0 ? round($totalScore / $scoredCount, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_students' => $totalStudents,
                'total_classes' => $totalClasses,
                'tasks_completed' => $tasksCompleted,
                'time_spent' => $timeSpentFormatted,
                'average_score' => $averageScore,
            ]
        ]);
    }

    /**
     * Reading module statistics for teacher dashboard.
     */
    public function readingStatistics(Request $request)
    {
        $userId = auth()->id();

        // Tasks created by this teacher
        $tasksCreated = DB::table('reading_tasks')->where('created_by', $userId)->count();

        // Submissions base query with class filter
        $base = DB::table('reading_submissions')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
            ->where(function ($q) use ($userId) {
                $q->where('tests.creator_id', $userId)
                  ->orWhere('reading_tasks.created_by', $userId);
            })
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('reading_submissions.submitted_at');

        $tasksCompleted = (clone $base)->count();
        $totalSeconds = (clone $base)->sum('reading_submissions.time_taken_seconds');
        $avgScore = (clone $base)->avg('reading_submissions.percentage');

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpent = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpent = "0h";

        // Unique students who submitted
        $totalStudents = (clone $base)->distinct('reading_submissions.student_id')
            ->count('reading_submissions.student_id');

        // Recent submissions (last 10)
        $recentSubmissions = DB::table('reading_submissions')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
            ->join('users', 'reading_submissions.student_id', '=', 'users.id')
            ->where(function ($q) use ($userId) {
                $q->where('tests.creator_id', $userId)
                  ->orWhere('reading_tasks.created_by', $userId);
            })
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('reading_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('COALESCE(reading_tasks.title, tests.title) as task_title'),
                'reading_submissions.percentage as score',
                'reading_submissions.submitted_at'
            )
            ->orderBy('reading_submissions.submitted_at', 'desc')
            ->limit(10)
            ->get();

        // Top performers (top 5 students by avg score)
        $topPerformers = DB::table('reading_submissions')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
            ->join('users', 'reading_submissions.student_id', '=', 'users.id')
            ->where(function ($q) use ($userId) {
                $q->where('tests.creator_id', $userId)
                  ->orWhere('reading_tasks.created_by', $userId);
            })
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('reading_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(reading_submissions.percentage), 0) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('reading_submissions.student_id', 'users.name')
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();

        // Struggling students (avg < 50)
        $strugglingStudents = DB::table('reading_submissions')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
            ->join('users', 'reading_submissions.student_id', '=', 'users.id')
            ->where(function ($q) use ($userId) {
                $q->where('tests.creator_id', $userId)
                  ->orWhere('reading_tasks.created_by', $userId);
            })
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('reading_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(reading_submissions.percentage), 0) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('reading_submissions.student_id', 'users.name')
            ->havingRaw('AVG(reading_submissions.percentage) < 50')
            ->orderBy('average_score', 'asc')
            ->limit(5)
            ->get();

        // Performance Trend (last 6 months)
        $performanceTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthlyAvg = DB::table('reading_submissions')
                ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
                ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
                ->leftJoin('assignments', 'reading_submissions.assignment_id', '=', 'assignments.id')
                ->where(function ($q) use ($userId) {
                    $q->where('tests.creator_id', $userId)
                      ->orWhere('reading_tasks.created_by', $userId);
                })
                ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                    return $q->where('assignments.class_id', $request->class_id);
                })
                ->whereBetween('reading_submissions.submitted_at', [$monthStart, $monthEnd])
                ->avg('reading_submissions.percentage');

            $performanceTrends[] = [
                'month' => $date->format('M'),
                'avgScore' => round($monthlyAvg ?? 0, 0)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'total_students' => $totalStudents,
                'time_spent' => $timeSpent,
                'average_score' => round($avgScore ?? 0, 0),
                'recent_submissions' => $recentSubmissions,
                'top_performers' => $topPerformers,
                'struggling_students' => $strugglingStudents,
                'performance_trends' => $performanceTrends,
                'category_performance' => [
                    ['category' => 'Multiple Choice', 'score' => 75],
                    ['category' => 'True/False/Not Given', 'score' => 68],
                    ['category' => 'Matching Headings', 'score' => 82],
                    ['category' => 'Sentence Completion', 'score' => 70]
                ]
            ]
        ]);
    }

    /**
     * Writing module statistics for teacher dashboard.
     */
    public function writingStatistics(Request $request)
    {
        $userId = auth()->id();

        $tasksCreated = DB::table('writing_tasks')->where('creator_id', $userId)->count();

        $base = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at');

        $tasksCompleted = (clone $base)->count();
        $totalSeconds = (clone $base)->sum('writing_submissions.time_taken_seconds');

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpent = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpent = "0h";

        $totalStudents = (clone $base)->distinct('writing_submissions.student_id')
            ->count('writing_submissions.student_id');

        // Writing scores come from writing_reviews
        $scoreData = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->join('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at')
            ->selectRaw('ROUND(AVG(writing_reviews.score), 1) as avg_score, COUNT(writing_reviews.id) as review_count')
            ->first();

        $pendingReviews = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->leftJoin('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at')
            ->whereNull('writing_reviews.id')
            ->count();

        // Recent submissions
        $recentSubmissions = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->join('users', 'writing_submissions.student_id', '=', 'users.id')
            ->leftJoin('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                'writing_tasks.title as task_title',
                'writing_reviews.score as score',
                'writing_submissions.submitted_at',
                DB::raw('IF(writing_reviews.id IS NULL, "pending", "reviewed") as review_status')
            )
            ->orderBy('writing_submissions.submitted_at', 'desc')
            ->limit(10)
            ->get();

        // Top performers by review score
        $topPerformers = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->join('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->join('users', 'writing_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('writing_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(writing_reviews.score), 1) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('writing_submissions.student_id', 'users.name')
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();

        // Performance Trend (last 6 months)
        $performanceTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthlyAvg = DB::table('writing_submissions')
                ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
                ->join('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
                ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
                ->where('writing_tasks.creator_id', $userId)
                ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                    return $q->where('assignments.class_id', $request->class_id);
                })
                ->whereBetween('writing_submissions.submitted_at', [$monthStart, $monthEnd])
                ->avg('writing_reviews.score');

            $performanceTrends[] = [
                'month' => $date->format('M'),
                'avgScore' => round($monthlyAvg ?? 0, 0)
            ];
        }

        // Criteria Mastery
        $reviews = DB::table('writing_submissions')
            ->join('writing_tasks', 'writing_submissions.writing_task_id', '=', 'writing_tasks.id')
            ->join('writing_reviews', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->leftJoin('assignments', 'writing_submissions.assignment_id', '=', 'assignments.id')
            ->where('writing_tasks.creator_id', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->select('writing_reviews.feedback_json')
            ->get();

        $criteriaTotals = ['task_response' => 0, 'coherence' => 0, 'lexical' => 0, 'grammar' => 0];
        $criteriaCounts = ['task_response' => 0, 'coherence' => 0, 'lexical' => 0, 'grammar' => 0];

        foreach ($reviews as $review) {
            $fj = json_decode($review->feedback_json, true);
            if ($fj && is_array($fj)) {
                $map = [
                    'task_response' => 'task_response',
                    'coherence_cohesion' => 'coherence',
                    'lexical_resource' => 'lexical',
                    'grammatical_range_accuracy' => 'grammar'
                ];
                foreach ($map as $dbKey => $feKey) {
                    if (isset($fj[$dbKey])) {
                        $criteriaTotals[$feKey] += (float)$fj[$dbKey];
                        $criteriaCounts[$feKey]++;
                    }
                }
            }
        }

        $criteriaMastery = [];
        $labels = [
            'task_response' => 'Task Response',
            'coherence' => 'Cohesion',
            'lexical' => 'Vocabulary',
            'grammar' => 'Grammar'
        ];
        foreach ($criteriaTotals as $key => $total) {
            $count = $criteriaCounts[$key] ?? 0;
            $avg = $count > 0 ? ($total / $count) : 0;
            $criteriaMastery[] = [
                'id' => $key,
                'label' => $labels[$key],
                'score' => round(($avg / 9) * 100, 0)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'total_students' => $totalStudents,
                'time_spent' => $timeSpent,
                'average_score' => $scoreData ? round($scoreData->avg_score ?? 0, 1) : 0,
                'reviewed_count' => $scoreData ? ($scoreData->review_count ?? 0) : 0,
                'pending_reviews' => $pendingReviews,
                'recent_submissions' => $recentSubmissions,
                'top_performers' => $topPerformers,
                'performance_trends' => $performanceTrends,
                'criteria_mastery' => $criteriaMastery
            ]
        ]);
    }

    /**
     * Listening module statistics for teacher dashboard.
     */
    public function listeningStatistics(Request $request)
    {
        $userId = auth()->id();

        $tasksCreated = DB::table('listening_tasks')->where('created_by', $userId)->count();

        $base = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at');

        $tasksCompleted = (clone $base)->count();
        $totalSeconds = (clone $base)->sum('listening_submissions.time_taken_seconds');
        $avgScore = (clone $base)->avg('listening_submissions.percentage');

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpent = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpent = "0h";

        $totalStudents = (clone $base)->distinct('listening_submissions.student_id')
            ->count('listening_submissions.student_id');

        // Recent submissions
        $recentSubmissions = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->join('users', 'listening_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                'listening_tasks.title as task_title',
                'listening_submissions.percentage as score',
                'listening_submissions.submitted_at'
            )
            ->orderBy('listening_submissions.submitted_at', 'desc')
            ->limit(10)
            ->get();

        // Top performers
        $topPerformers = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->join('users', 'listening_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(listening_submissions.percentage), 0) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('listening_submissions.student_id', 'users.name')
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();

        // Struggling students
        $strugglingStudents = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->join('users', 'listening_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(listening_submissions.percentage), 0) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('listening_submissions.student_id', 'users.name')
            ->havingRaw('AVG(listening_submissions.percentage) < 50')
            ->orderBy('average_score', 'asc')
            ->limit(5)
            ->get();

        // Performance Trends (last 6 months)
        $performanceTrends = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at')
            ->select(
                DB::raw("DATE_FORMAT(listening_submissions.submitted_at, '%Y-%m') as month"),
                DB::raw('ROUND(AVG(listening_submissions.percentage), 0) as score')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(6)
            ->get();

        // Popular Tasks
        $popularTasks = DB::table('listening_submissions')
            ->join('listening_tasks', 'listening_submissions.listening_task_id', '=', 'listening_tasks.id')
            ->leftJoin('assignments', 'listening_submissions.assignment_id', '=', 'assignments.id')
            ->where('listening_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('listening_submissions.submitted_at')
            ->select(
                'listening_tasks.title as task_title',
                DB::raw('COUNT(*) as submission_count')
            )
            ->groupBy('listening_tasks.id', 'listening_tasks.title')
            ->orderByDesc('submission_count')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'total_students' => $totalStudents,
                'time_spent' => $timeSpent,
                'average_score' => round($avgScore ?? 0, 0),
                'recent_submissions' => $recentSubmissions,
                'top_performers' => $topPerformers,
                'struggling_students' => $strugglingStudents,
                'performance_trends' => $performanceTrends,
                'popular_tasks' => $popularTasks,
            ]
        ]);
    }
    /**
     * Speaking module statistics for teacher dashboard.
     */
    public function speakingStatistics(Request $request)
    {
        $userId = auth()->id();

        $tasksCreated = DB::table('speaking_tasks')->where('created_by', $userId)->count();

        $base = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at');

        $tasksCompleted = (clone $base)->count();
        $totalSeconds = (clone $base)->sum('speaking_submissions.total_time_seconds');

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpent = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpent = "0h";

        $totalStudents = (clone $base)->distinct('speaking_submissions.student_id')
            ->count('speaking_submissions.student_id');

        // Speaking scores come from speaking_reviews
        $scoreData = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->selectRaw('ROUND(AVG(speaking_reviews.total_score), 1) as avg_score, COUNT(speaking_reviews.id) as review_count')
            ->first();

        $pendingReviews = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->leftJoin('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->whereNull('speaking_reviews.id')
            ->count();

        // Recent submissions
        $recentSubmissions = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('users', 'speaking_submissions.student_id', '=', 'users.id')
            ->leftJoin('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                'speaking_tasks.title as task_title',
                'speaking_reviews.total_score as score',
                'speaking_submissions.submitted_at',
                DB::raw('IF(speaking_reviews.id IS NULL, "pending", "reviewed") as review_status')
            )
            ->orderBy('speaking_submissions.submitted_at', 'desc')
            ->limit(10)
            ->get();

        // Top performers
        $topPerformers = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->join('users', 'speaking_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(speaking_reviews.total_score), 1) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('speaking_submissions.student_id', 'users.name')
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();

        // Struggling students
        $strugglingStudents = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->join('users', 'speaking_submissions.student_id', '=', 'users.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->select(
                'users.name as student_name',
                DB::raw('ROUND(AVG(speaking_reviews.total_score), 1) as average_score'),
                DB::raw('COUNT(*) as total_submissions')
            )
            ->groupBy('speaking_submissions.student_id', 'users.name')
            ->havingRaw('AVG(speaking_reviews.total_score) < 5')
            ->orderBy('average_score', 'asc')
            ->limit(5)
            ->get();

        // Performance Trend (last 6 months)
        $performanceTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthlyAvg = DB::table('speaking_submissions')
                ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
                ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
                ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
                ->where('speaking_tasks.created_by', $userId)
                ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                    return $q->where('assignments.class_id', $request->class_id);
                })
                ->whereBetween('speaking_submissions.submitted_at', [$monthStart, $monthEnd])
                ->avg('speaking_reviews.total_score');

            $performanceTrends[] = [
                'month' => $date->format('M'),
                'score' => round($monthlyAvg ?? 0, 0)
            ];
        }

        // Popular Tasks
        $popularTasks = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_submissions.submitted_at')
            ->select(
                'speaking_tasks.title as task_title',
                DB::raw('COUNT(*) as submission_count')
            )
            ->groupBy('speaking_tasks.id', 'speaking_tasks.title')
            ->orderByDesc('submission_count')
            ->limit(5)
            ->get();

        // Skill Mastery / Criteria Mastery
        $reviews = DB::table('speaking_submissions')
            ->join('speaking_tasks', 'speaking_submissions.speaking_task_id', '=', 'speaking_tasks.id')
            ->join('speaking_reviews', 'speaking_reviews.submission_id', '=', 'speaking_submissions.id')
            ->leftJoin('assignments', 'speaking_submissions.assignment_id', '=', 'assignments.id')
            ->where('speaking_tasks.created_by', $userId)
            ->when($request->class_id && $request->class_id !== 'all', function ($q) use ($request) {
                return $q->where('assignments.class_id', $request->class_id);
            })
            ->whereNotNull('speaking_reviews.skill_scores')
            ->select('speaking_reviews.skill_scores')
            ->get();

        $criteriaTotals = ['fluency' => 0, 'pronunciation' => 0, 'vocabulary' => 0, 'grammar' => 0];
        $criteriaCounts = ['fluency' => 0, 'pronunciation' => 0, 'vocabulary' => 0, 'grammar' => 0];

        foreach ($reviews as $review) {
            $scores = json_decode($review->skill_scores, true);
            if ($scores && is_array($scores)) {
                foreach ($criteriaTotals as $key => $val) {
                    if (isset($scores[$key])) {
                        $criteriaTotals[$key] += (float)$scores[$key];
                        $criteriaCounts[$key]++;
                    }
                }
            }
        }

        $criteriaMastery = [];
        $labels = [
            'fluency' => 'Fluency & Coherence',
            'pronunciation' => 'Pronunciation',
            'vocabulary' => 'Lexical Resource',
            'grammar' => 'Grammatical Range'
        ];
        foreach ($criteriaTotals as $key => $total) {
            $count = $criteriaCounts[$key] ?? 0;
            $avg = $count > 0 ? ($total / $count) : 0;
            $criteriaMastery[] = [
                'id' => $key,
                'label' => $labels[$key],
                'score' => round(($avg / 9) * 100, 0)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks_created' => $tasksCreated,
                'tasks_completed' => $tasksCompleted,
                'total_students' => $totalStudents,
                'time_spent' => $timeSpent,
                'average_score' => $scoreData ? round($scoreData->avg_score ?? 0, 1) : 0,
                'reviewed_count' => $scoreData ? ($scoreData->review_count ?? 0) : 0,
                'pending_reviews' => $pendingReviews,
                'recent_submissions' => $recentSubmissions,
                'top_performers' => $topPerformers,
                'struggling_students' => $strugglingStudents,
                'performance_trends' => $performanceTrends,
                'popular_tasks' => $popularTasks,
                'criteria_mastery' => $criteriaMastery,
            ]
        ]);
    }
}


