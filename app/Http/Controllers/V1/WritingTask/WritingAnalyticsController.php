<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Models\WritingSubmission;
use App\Models\WritingTask;
use App\Models\WritingReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WritingAnalyticsController extends Controller
{
    /**
     * Get analytics for the logged in student.
     */
    public function studentAnalytics(Request $request)
    {
        $studentId = Auth::id();
        $month = $request->query('month');
        
        // 1. Score Progress (Trend)
        $query = WritingSubmission::where('student_id', $studentId)
            ->where('status', 'reviewed');

        if ($month) {
            try {
                $startDate = Carbon::parse($month . '-01')->startOfMonth();
                $endDate = Carbon::parse($month . '-01')->endOfMonth();
                $query->whereBetween('submitted_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $submissions = $query->with(['latestReview', 'task'])
            ->orderBy('submitted_at', 'asc')
            ->get();

        $scoreTrend = $submissions->map(function ($sub) {
            return [
                'date' => $sub->submitted_at ? $sub->submitted_at->format('Y-m-d') : $sub->created_at->format('Y-m-d'),
                'score' => $sub->latestReview ? (float)$sub->latestReview->score : 0,
                'title' => $sub->task->title ?? 'Untitled Task',
            ];
        });

        // 2. IELTS Criteria (Radar Chart) - Use best attempt per task for radar to be fair
        $criteriaTotals = [
            'task_response' => 0,
            'coherence_cohesion' => 0,
            'lexical_resource' => 0,
            'grammatical_range_accuracy' => 0,
        ];
        $criteriaCount = 0;

        // Group by task to get best attempt per task
        $bestSubmissionsPerTask = $submissions->groupBy('writing_task_id')->map(function($taskWork) {
            return $taskWork->sortByDesc('latestReview.score')->first();
        });

        foreach ($bestSubmissionsPerTask as $sub) {
            if ($sub->latestReview && is_array($sub->latestReview->feedback_json)) {
                $fj = $sub->latestReview->feedback_json;
                $hasData = false;
                if (isset($fj['task_response'])) { $criteriaTotals['task_response'] += (float)$fj['task_response']; $hasData = true; }
                if (isset($fj['coherence_cohesion'])) { $criteriaTotals['coherence_cohesion'] += (float)$fj['coherence_cohesion']; $hasData = true; }
                if (isset($fj['lexical_resource'])) { $criteriaTotals['lexical_resource'] += (float)$fj['lexical_resource']; $hasData = true; }
                if (isset($fj['grammatical_range_accuracy'])) { $criteriaTotals['grammatical_range_accuracy'] += (float)$fj['grammatical_range_accuracy']; $hasData = true; }
                if ($hasData) $criteriaCount++;
            }
        }

        $radarData = [];
        $displayNames = [
            'task_response' => 'Task Response',
            'coherence_cohesion' => 'Coherence',
            'lexical_resource' => 'Vocabulary',
            'grammatical_range_accuracy' => 'Grammar'
        ];

        if ($criteriaCount > 0) {
            foreach ($criteriaTotals as $key => $total) {
                $radarData[] = [
                    'criterion' => $displayNames[$key],
                    'value' => round($total / $criteriaCount, 1),
                ];
            }
        }

        // 3. Task 1 vs Task 2 comparison
        $task1Scores = [];
        $task2Scores = [];

        foreach ($submissions as $sub) {
            $taskType = $sub->task->task_type ?? 'essay';
            if ($sub->latestReview) {
                if ($taskType === 'report') {
                    $task1Scores[] = $sub->latestReview->score;
                } else {
                    $task2Scores[] = $sub->latestReview->score;
                }
            }
        }

        $comparison = [
            'task_1_avg' => count($task1Scores) > 0 ? round(array_sum($task1Scores) / count($task1Scores), 1) : 0,
            'task_2_avg' => count($task2Scores) > 0 ? round(array_sum($task2Scores) / count($task2Scores), 1) : 0,
        ];

        // 4. Time Taken Analysis
        $avgTimeTaken = $submissions->avg('time_taken_seconds') ?? 0;
        
        return response()->json([
            'data' => [
                'score_trend' => $scoreTrend,
                'radar_data' => $radarData,
                'comparison' => $comparison,
                'avg_time_taken_minutes' => round($avgTimeTaken / 60, 1),
                'total_completed' => $submissions->count(),
            ]
        ]);
    }

    /**
     * Get analytics for teachers.
     */
    public function teacherAnalytics(Request $request)
    {
        $teacherId = Auth::id();
        $classId = $request->query('class_id');
        $month = $request->query('month');
        
        // Get all classes taught by this teacher for the filter dropdown
        $classes = DB::table('classes')
            ->where('teacher_id', $teacherId)
            ->select('id', 'name')
            ->get();

        // Filter by Date Range
        $startDate = $request->query('from');
        $endDate = $request->query('to');

        // Query for tasks
        $tasksQuery = WritingTask::where('creator_id', $teacherId);

        if ($classId && $classId !== 'all') {
            $tasksQuery->whereHas('assignments', function ($q) use ($classId, $teacherId) {
                $q->where('class_id', $classId)
                  ->whereHas('class', function($cq) use ($teacherId) {
                      $cq->where('teacher_id', $teacherId);
                  });
            });
        }

        $tasks = $tasksQuery->with(['submissions' => function ($q) use ($classId, $startDate, $endDate, $month) {
            if ($classId && $classId !== 'all') {
                $q->whereHas('student', function ($sq) use ($classId) {
                    $sq->whereHas('enrollments', function ($eq) use ($classId) {
                        $eq->where('class_id', $classId)->where('status', 'active');
                    });
                });
            }
            
            if ($startDate) {
                $q->whereDate('submitted_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('submitted_at', '<=', $endDate);
            }

            if ($month) {
                try {
                    $mStart = Carbon::parse($month . '-01')->startOfMonth();
                    $mEnd = Carbon::parse($month . '-01')->endOfMonth();
                    $q->whereBetween('submitted_at', [$mStart, $mEnd]);
                } catch (\Exception $e) {}
            }

            $q->with(['latestReview', 'task']);
        }])->get();

        $totalSubmissions = 0;
        $allStudentBestScores = [];
        $task1Scores = [];
        $task2Scores = [];
        $criteriaTotals = [
            'task_response' => 0,
            'coherence_cohesion' => 0,
            'lexical_resource' => 0,
            'grammatical_range_accuracy' => 0
        ];
        $criteriaCounts = [
            'task_response' => 0,
            'coherence_cohesion' => 0,
            'lexical_resource' => 0,
            'grammatical_range_accuracy' => 0
        ];
        
        foreach ($tasks as $task) {
            // Group task submissions by student to get their best score for class average
            $studentBestSubmissions = $task->submissions->groupBy('student_id')->map(function($studentWork) {
                return $studentWork->filter(function($sub) {
                    return ($sub->status === 'reviewed' || $sub->status === 'done') && $sub->latestReview;
                })->sortByDesc(function($sub) {
                    return (float)$sub->latestReview->score;
                })->first();
            })->filter(fn($sub) => !is_null($sub));

            foreach ($task->submissions as $sub) {
                $totalSubmissions++;
            }

            foreach ($studentBestSubmissions as $sub) {
                $score = (float)$sub->latestReview->score;
                $allStudentBestScores[] = $score;
                
                // Track Task 1 vs Task 2
                $taskType = $sub->task->task_type ?? 'essay';
                if ($taskType === 'report') {
                    $task1Scores[] = $score;
                } else {
                    $task2Scores[] = $score;
                }

                // Aggregate criteria stats from best attempts
                $fj = $sub->latestReview->feedback_json;
                if ($fj && is_array($fj)) {
                    foreach (array_keys($criteriaTotals) as $key) {
                        if (isset($fj[$key]) && is_numeric($fj[$key])) {
                            $criteriaTotals[$key] += (float)$fj[$key];
                            $criteriaCounts[$key]++;
                        }
                    }
                }
            }
        }

        $totalReviewed = count($allStudentBestScores);
        $avgClassScore = $totalReviewed > 0 ? array_sum($allStudentBestScores) / $totalReviewed : 0;

        // Calculate mastery percentages for each criteria
        $criteriaMastery = [];
        foreach ($criteriaTotals as $key => $total) {
            $count = $criteriaCounts[$key];
            // Mastery is (average score / 9) * 100
            $mastery = $count > 0 ? round(($total / ($count * 9)) * 100, 0) : 0;
            $criteriaMastery[] = [
                'id' => $key,
                'name' => ucwords(str_replace('_', ' ', $key)),
                'score' => (int)$mastery
            ];
        }

        // Determine best and weakest criteria
        usort($criteriaMastery, fn($a, $b) => $b['score'] <=> $a['score']);
        $bestCriteria = count($criteriaMastery) > 0 ? $criteriaMastery[0] : null;
        $weakestCriteria = count($criteriaMastery) > 0 ? end($criteriaMastery) : null;

        return response()->json([
            'data' => [
                'total_tasks' => $tasks->count(),
                'total_submissions' => $totalSubmissions,
                'reviewed_count' => $totalReviewed,
                'average_score' => round($avgClassScore, 1),
                'task_type_comparison' => [
                    'task_1_avg' => count($task1Scores) > 0 ? round(array_sum($task1Scores) / count($task1Scores), 1) : 0,
                    'task_2_avg' => count($task2Scores) > 0 ? round(array_sum($task2Scores) / count($task2Scores), 1) : 0,
                ],
                'best_criteria' => $bestCriteria,
                'weakest_criteria' => $weakestCriteria,
                'criteria_mastery' => $criteriaMastery,
                'classes' => $classes,
                'current_class_id' => $classId
            ]
        ]);
    }

    /**
     * Get detailed report for a specific writing task.
     */
    public function taskReport(Request $request, $id)
    {
        $teacherId = Auth::id();
        
        // 1. Get Task and verify ownership
        $task = WritingTask::where('id', $id)
            ->where('creator_id', $teacherId)
            ->with(['assignments.class'])
            ->firstOrFail();

        // 2. Query submissions for this task
        $query = WritingSubmission::where('writing_task_id', $id)
            ->with(['latestReview', 'student']);

        // --- FILTERING ---
        // Filter by Status (ontime, late, pending)
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            $query->where(function($q) use ($statuses) {
                foreach ($statuses as $status) {
                    $status = strtolower(trim($status));
                    if ($status === 'pending') {
                        $q->orWhere('status', 'pending');
                    } elseif ($status === 'ontime') {
                        $q->orWhere(function($subQ) {
                            $subQ->where('status', '!=', 'pending')
                                 ->where(function($timeQ) {
                                     $timeQ->whereNull('deadline_at')
                                           ->orWhereColumn('submitted_at', '<=', 'deadline_at');
                                 });
                        });
                    } elseif ($status === 'late') {
                        $q->orWhere(function($subQ) {
                            $subQ->where('status', '!=', 'pending')
                                 ->whereColumn('submitted_at', '>', 'deadline_at');
                        });
                    }
                }
            });
        }

        // Filter by Date Range
        if ($request->has('from')) {
            $query->whereDate('submitted_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('submitted_at', '<=', $request->to);
        }

        // Filter by Score Range
        if ($request->has('minScore') || $request->has('maxScore')) {
            $query->whereHas('latestReview', function($q) use ($request) {
                if ($request->has('minScore')) {
                    $q->where('score', '>=', $request->minScore);
                }
                if ($request->has('maxScore')) {
                    $q->where('score', '<=', $request->maxScore);
                }
            });
        }

        $submissions = $query->get();

        // Group by student to handle leaderboard and stats fairly (per student's best attempt)
        $studentBestSubmissions = $submissions->groupBy('student_id')->map(function($studentWork) {
            // Sort to get the highest scored attempt, then latest date
            return $studentWork->sort(function($a, $b) {
                $scoreA = $a->latestReview ? (float)$a->latestReview->score : -1;
                $scoreB = $b->latestReview ? (float)$b->latestReview->score : -1;
                
                if ($scoreA != $scoreB) {
                    return $scoreB <=> $scoreA;
                }
                
                $dateA = $a->submitted_at ? $a->submitted_at->timestamp : 0;
                $dateB = $b->submitted_at ? $b->submitted_at->timestamp : 0;
                return $dateB <=> $dateA;
            })->first();
        });

        $reviewedSubmissions = $studentBestSubmissions->filter(function($sub) {
            return ($sub->status === 'reviewed' || $sub->status === 'done') && $sub->latestReview;
        });

        // 3. Calculate Stats
        $scores = $reviewedSubmissions->map(fn($s) => (float)$s->latestReview->score);
        
        $stats = [
            'highest_score' => $scores->count() > 0 ? (float)$scores->max() : 0,
            'average_score' => $scores->count() > 0 ? round($scores->average(), 1) : 0,
            'lowest_score' => $scores->count() > 0 ? (float)$scores->min() : 0,
        ];

        // 4. Criteria Analysis
        $criteriaTotals = [
            'task_response' => 0,
            'coherence_cohesion' => 0,
            'lexical_resource' => 0,
            'grammatical_range_accuracy' => 0,
        ];
        $criteriaCounts = [
            'task_response' => 0,
            'coherence_cohesion' => 0,
            'lexical_resource' => 0,
            'grammatical_range_accuracy' => 0,
        ];

        foreach ($reviewedSubmissions as $sub) {
            $fj = $sub->latestReview->feedback_json;
            if ($fj && is_array($fj)) {
                foreach (array_keys($criteriaTotals) as $key) {
                    if (isset($fj[$key]) && is_numeric($fj[$key])) {
                        $criteriaTotals[$key] += (float)$fj[$key];
                        $criteriaCounts[$key]++;
                    }
                }
            }
        }

        $criteriaPerformance = [];
        $displayNames = [
            'task_response' => 'Task Response',
            'coherence_cohesion' => 'Coherence & Cohesion',
            'lexical_resource' => 'Lexical Resource',
            'grammatical_range_accuracy' => 'Grammar Accuracy'
        ];

        foreach ($criteriaTotals as $key => $total) {
            $count = $criteriaCounts[$key];
            $accuracy = $count > 0 ? round(($total / ($count * 9)) * 100, 0) : 0;

            $criteriaPerformance[] = [
                'title' => $displayNames[$key],
                'accurate' => (int)$accuracy,
                'score' => $count > 0 ? round($total / $count, 1) : 0
            ];
        }

        // Sort by accuracy
        $bestPerformance = $criteriaPerformance;
        usort($bestPerformance, fn($a, $b) => $b['accurate'] <=> $a['accurate']);
        $weakPerformance = array_reverse($bestPerformance);

        // 5. Leaderboard (Using student best submissions) with Pagination
        $perPage = (int)$request->query('per_page', 10);
        $page = (int)$request->query('page', 1);

        $allLeaderboard = $studentBestSubmissions->map(function($sub) {
            $status = 'ontime';
            if ($sub->submitted_at && $sub->deadline_at && $sub->submitted_at->gt($sub->deadline_at)) {
                $status = 'late';
            }
            
            return [
                'id' => $sub->id,
                'student_name' => $sub->student->name ?? 'Unknown Student',
                'submission_date' => $sub->submitted_at ? $sub->submitted_at->format('d M Y') : '-',
                'status' => $sub->status === 'pending' ? 'pending' : $status,
                'score' => $sub->latestReview ? (float)$sub->latestReview->score : 0,
            ];
        })->sortByDesc('score')->values();

        $paginatedLeaderboard = $allLeaderboard->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => [
                'task_name' => $task->title,
                'attempts' => $submissions->count(),
                'class' => $task->assignments->map(fn($a) => $a->class?->name)->filter()->unique()->implode(', '),
                'created_at' => $task->created_at->format('d M Y'),
                'stats' => $stats,
                'best_performance' => array_slice($bestPerformance, 0, 3),
                'weak_performance' => array_slice($weakPerformance, 0, 3),
                'leaderboard' => $paginatedLeaderboard,
                'leaderboard_meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $allLeaderboard->count(),
                    'last_page' => ceil($allLeaderboard->count() / $perPage)
                ]
            ]
        ]);
    }
}

