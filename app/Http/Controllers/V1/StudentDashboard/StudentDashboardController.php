<?php

namespace App\Http\Controllers\V1\StudentDashboard;

use App\Http\Controllers\Controller;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    /**
     * Get student dashboard with assignments from all enrolled classes
     */
    public function dashboard(Request $request): JsonResponse
    {
        $studentId = auth()->id();
        $search = $request->query('search');
        $module = $request->query('module');
        $status = $request->query('status');
        $perPage = $request->query('size', 10);

        // Get all classes the student is enrolled in
        $enrolledClassIds = ClassEnrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('class_id');

        // --- Summary Stats Calculation ---
        // Fetch all assignments for these classes once to calculate counts efficiently
        $allAssignments = \App\Models\Assignment::whereIn('class_id', $enrolledClassIds)
            ->with(['studentAssignments' => function($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }])
            ->get();

        $completedCount = 0;
        $pendingCount = 0;
        $overdueCount = 0;
        $scoreSum = 0;
        $scoredCount = 0;

        foreach ($allAssignments as $assignment) {
            $studentAssignment = $assignment->studentAssignments->first();
            $asgnStatus = $studentAssignment?->status ?? 'pending';

            if (in_array($asgnStatus, ['completed', 'submitted', 'graded', 'done'])) {
                $completedCount++;
                if ($studentAssignment && $studentAssignment->score !== null) {
                    $scoreSum += $studentAssignment->score;
                    $scoredCount++;
                }
            } else {
                if ($assignment->due_date && $assignment->due_date->isPast()) {
                    $overdueCount++;
                } else {
                    $pendingCount++;
                }
            }
        }

        $averageOverallScore = $scoredCount > 0 ? round($scoreSum / $scoredCount, 1) : 0;

        // Calculate Time Spent across modules
        $readingSeconds = DB::table('reading_submissions')->where('student_id', $studentId)->sum('time_taken_seconds');
        $listeningSeconds = DB::table('listening_submissions')->where('student_id', $studentId)->sum('time_taken_seconds');
        $speakingSeconds = DB::table('speaking_submissions')->where('student_id', $studentId)->sum('total_time_seconds');
        $writingSeconds = DB::table('student_assignments')
            ->where('student_id', $studentId)
            ->where('assignment_type', 'writing_task')
            ->sum('time_spent_seconds');
        
        $totalSeconds = $readingSeconds + $listeningSeconds + $speakingSeconds + $writingSeconds;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpentFormatted = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpentFormatted = "0h";

        // --- Assignments List Query with Filters ---
        $query = \App\Models\Assignment::whereIn('class_id', $enrolledClassIds)
            ->with(['studentAssignments' => function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            }, 'class']);

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($module) {
            $modules = is_array($module) ? $module : explode(',', $module);
            $query->whereIn('type', $modules);
        }

        if ($status) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            
            // Map frontend statuses to backend statuses
            // Frontend keys: 'done', 'todo', 'review'
            $statusMap = [
                'done' => ['completed', 'submitted', 'graded', 'done'],
                'todo' => ['pending', 'not_started', 'in_progress'],
                'review' => ['reviewed']
            ];

            $mappedStatuses = [];
            foreach ($statuses as $s) {
                if (isset($statusMap[$s])) {
                    $mappedStatuses = array_merge($mappedStatuses, $statusMap[$s]);
                } else {
                    $mappedStatuses[] = $s;
                }
            }

            $query->where(function($q) use ($studentId, $mappedStatuses) {
                if (in_array('pending', $mappedStatuses) || in_array('not_started', $mappedStatuses)) {
                    $q->whereDoesntHave('studentAssignments', function($sq) use ($studentId) {
                        $sq->where('student_id', $studentId);
                    })->orWhereHas('studentAssignments', function($sq) use ($studentId, $mappedStatuses) {
                        $sq->where('student_id', $studentId)->whereIn('status', $mappedStatuses);
                    });
                } else {
                    $q->whereHas('studentAssignments', function($sq) use ($studentId, $mappedStatuses) {
                        $sq->where('student_id', $studentId)->whereIn('status', $mappedStatuses);
                    });
                }
            });
        }

        $paginatedAssignments = $query->orderBy('due_date')->paginate($perPage);

        $assignmentsData = collect($paginatedAssignments->items())->map(function($assignment) use ($studentId) {
            $studentAssignment = $assignment->studentAssignments->first();
            $task = $assignment->getTask();
            
            return [
                'id' => $assignment->id,
                'type' => $assignment->type ?? $assignment->task_type,
                'title' => $assignment->getAssignmentTitle(),
                'description' => $assignment->description ?? $task?->description,
                'class_name' => $assignment->class?->name ?? 'Unknown Class',
                'due_date' => $assignment->due_date,
                'assigned_date' => $assignment->created_at,
                'status' => $studentAssignment?->status ?? 'pending',
                'score' => $studentAssignment?->score,
                'completion_date' => $studentAssignment?->completed_at,
                'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                'max_attempts' => $assignment->max_attempts,
            ];
        });

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'student_name' => auth()->user()->name,
                'enrolled_classes' => $enrolledClassIds->count(),
                'total_assignments' => $allAssignments->count(),
                'completed_assignments' => $completedCount,
                'pending_assignments' => $pendingCount,
                'overdue_assignments' => $overdueCount,
                'average_score' => $averageOverallScore,
                'total_time_spent' => $timeSpentFormatted,
                'assignments' => $assignmentsData->values(),
                'pagination' => [
                    'current_page' => $paginatedAssignments->currentPage(),
                    'last_page' => $paginatedAssignments->lastPage(),
                    'per_page' => $paginatedAssignments->perPage(),
                    'total' => $paginatedAssignments->total(),
                ]
            ]
        ]);
    }

    /**
     * Get assignment details for starting
     */
    public function getAssignmentDetails(string $assignmentId, string $type): JsonResponse
    {
        $studentId = auth()->id();
        $type = $this->normalizeType($type);

        // Check if student has access to this assignment
        $hasAccess = $this->checkStudentAccess($assignmentId, $type, $studentId);
        
        if (!$hasAccess) {
            return response()->json([
                'message' => 'You do not have access to this assignment'
            ], 403);
        }

        // Get assignment details based on type
        $assignmentData = $this->getTaskByType($assignmentId, $type);
        
        if (!$assignmentData) {
            return response()->json([
                'message' => 'Assignment not found'
            ], 404);
        }

        // Resolve the actual assignment_id if a StudentAssignment ID was provided
        $resolvedAssignmentId = $assignmentId;
        $studentAssignment = StudentAssignment::where([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
        ])->first();

        if (!$studentAssignment) {
            $potentialSA = StudentAssignment::find($assignmentId);
            if ($potentialSA) {
                $studentAssignment = $potentialSA;
                $resolvedAssignmentId = $potentialSA->assignment_id;
            }
        }

        // Get or create student assignment record if still not found
        if (!$studentAssignment) {
            $studentAssignment = StudentAssignment::firstOrCreate([
                'student_id' => $studentId,
                'assignment_id' => $resolvedAssignmentId,
                'assignment_type' => $type
            ], [
                'status' => StudentAssignment::STATUS_NOT_STARTED,
                'attempt_count' => 0
            ]);
        }

        // Self-healing for inconsistent state (in_progress but already completed)
        if ($studentAssignment->status === StudentAssignment::STATUS_IN_PROGRESS && $studentAssignment->completed_at !== null) {
            $studentAssignment->update([
                'status' => StudentAssignment::STATUS_SUBMITTED,
                'last_activity_at' => now(),
            ]);
            $studentAssignment->refresh();
        }

        // Resolve description and difficulty from task or test
        $resolvedTask = $assignmentData->getTask();
        if (!$resolvedTask && $assignmentData->test_id) {
            $resolvedTask = \App\Models\Test::find($assignmentData->test_id);
        }

        return response()->json([
            'message' => 'Assignment details retrieved successfully',
            'data' => [
                'assignment' => $assignmentData->getAssignmentTitle(),
                'task_id' => $assignmentData->task_id ?? $assignmentData->test_id,
                'description' => $assignmentData->description ?? $resolvedTask?->description,
                'difficulty' => $resolvedTask?->difficulty ?? $resolvedTask?->difficulty_level,
                'due_date' => $assignmentData->due_date,
                'max_attempts' => $assignmentData->max_attempts ?? 3,
                'student_progress' => [
                    'status' => $studentAssignment->status,
                    'attempt_count' => $studentAssignment->attempt_count,
                    'score' => $studentAssignment->score,
                    'started_at' => $studentAssignment->started_at?->toIso8601String(),
                    'completed_at' => $studentAssignment->completed_at?->toIso8601String(),
                    'attempt_number' => $studentAssignment->attempt_number,
                ]
            ]
        ]);
    }

    /**
     * Start an assignment
     */
    public function startAssignment(Request $request, string $assignmentId, string $type): JsonResponse
    {
        $studentId = auth()->id();
        $type = $this->normalizeType($type);

        // Check access
        if (!$this->checkStudentAccess($assignmentId, $type, $studentId)) {
            return response()->json([
                'message' => 'You do not have access to this assignment'
            ], 403);
        }

        // Resolve the actual assignment_id if a StudentAssignment ID was provided
        $resolvedAssignmentId = $assignmentId;
        $studentAssignment = StudentAssignment::where([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
        ])->first();

        if (!$studentAssignment) {
            $potentialSA = StudentAssignment::find($assignmentId);
            if ($potentialSA) {
                $studentAssignment = $potentialSA;
                $resolvedAssignmentId = $potentialSA->assignment_id;
            }
        }

        // Get or create student assignment
        if (!$studentAssignment) {
            $studentAssignment = StudentAssignment::create([
                'student_id' => $studentId,
                'assignment_id' => $resolvedAssignmentId,
                'assignment_type' => $type,
                'status' => StudentAssignment::STATUS_IN_PROGRESS,
                'attempt_count' => 0,
                'started_at' => now()
            ]);
        }

        // If already exists, just update status to in_progress (don't increment attempts here;
        // the specific submission service like ListeningSubmissionService handles attempt tracking)
        if (!$studentAssignment->wasRecentlyCreated && $studentAssignment->status !== StudentAssignment::STATUS_IN_PROGRESS) {
            $updateData = [
                'status' => StudentAssignment::STATUS_IN_PROGRESS,
                'last_activity_at' => now(),
            ];

            // Set started_at if not already set
            if (!$studentAssignment->started_at) {
                $updateData['started_at'] = now();
            }

            // If it was already finished, increment attempt count for a retake
            if (in_array($studentAssignment->status, [StudentAssignment::STATUS_SUBMITTED, StudentAssignment::STATUS_COMPLETED, StudentAssignment::STATUS_GRADED])) {
                $nextAttempt = ($studentAssignment->attempt_count ?? 0) + 1;
                $updateData['attempt_count'] = $nextAttempt;
                $updateData['attempt_number'] = $nextAttempt;
                $updateData['score'] = 0;
                $updateData['completed_at'] = null;
                $updateData['started_at'] = now();
            }

            $studentAssignment->update($updateData);
        }

        return response()->json([
            'message' => 'Assignment started successfully',
            'data' => [
                'student_assignment_id' => $studentAssignment->id,
                'attempt_number' => $studentAssignment->attempt_count,
                'started_at' => $studentAssignment->started_at
            ]
        ]);
    }

    /**
     * Submit an assignment
     */
    public function submitAssignment(Request $request, string $assignmentId, string $type): JsonResponse
    {
        $studentId = auth()->id();
        $type = $this->normalizeType($type);

        // Find the student assignment regardless of status (the listening service may have already
        // updated it to 'submitted' with a score before this generic endpoint is called)
        $studentAssignment = StudentAssignment::where([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
            'assignment_type' => $type,
        ])->first();

        if (!$studentAssignment) {
            return response()->json([
                'message' => 'No active assignment found'
            ], 404);
        }

        // Only update if not already submitted (avoid overwriting score set by grading service)
        if ($studentAssignment->status === StudentAssignment::STATUS_IN_PROGRESS) {
            $studentAssignment->update([
                'status' => StudentAssignment::STATUS_SUBMITTED,
                'completed_at' => now(),
                'last_activity_at' => now(),
                'submission_data' => $request->input('submission_data', [])
            ]);
        }

        // Refresh to get latest data (may have been updated by grading service)
        $studentAssignment->refresh();

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'data' => [
                'student_assignment_id' => $studentAssignment->id,
                'completed_at' => $studentAssignment->completed_at,
                'submission_status' => $studentAssignment->status,
                'score' => $studentAssignment->score,
                'attempt_count' => $studentAssignment->attempt_count,
            ]
        ]);
    }

    /**
     * Check if student has access to assignment
     */
    private function checkStudentAccess(string $assignmentId, string $type, string $userId): bool
    {
        $assignment = \App\Models\Assignment::find($assignmentId);
        
        if (!$assignment) {
            $studentAssignment = \App\Models\StudentAssignment::find($assignmentId);
            if ($studentAssignment) {
                $assignment = $studentAssignment->assignment;
            }
        }

        if (!$assignment) {
            return false;
        }

        $user = auth()->user();
        
        // Allow teachers to preview the assignment if they own the class or created it
        if ($user->role === 'teacher' || $user->role === 'admin') {
            return $user->teacherClasses()->where('id', $assignment->class_id)->exists()
                || $assignment->assigned_by === $user->id;
        }

        // Check if student is enrolled in the class
        return ClassEnrollment::where([
            'student_id' => $userId,
            'class_id' => $assignment->class_id,
            'status' => 'active'
        ])->exists();
    }

    /**
     * Get task/test data for a student assignment (for taking the test)
     */
    public function getAssignmentTask(string $assignmentId, string $type): JsonResponse
    {
        $studentId = auth()->id();
        $type = $this->normalizeType($type);

        if (!$this->checkStudentAccess($assignmentId, $type, $studentId)) {
            return response()->json(['message' => 'You do not have access to this assignment'], 403);
        }

        $assignment = \App\Models\Assignment::find($assignmentId);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        // Try task first, then fall back to test
        $task = $assignment->getTask();

        if (!$task && $assignment->test_id) {
            $test = \App\Models\Test::with(['passages.questionGroups.questions.options'])->find($assignment->test_id);
            if (!$test) {
                return response()->json(['message' => 'Task not found'], 404);
            }

            // Convert Test structure to ReadingTask-compatible format
            $passages = $test->passages->map(function ($passage) {
                return [
                    'id' => $passage->id,
                    'title' => $passage->title,
                    'content' => $passage->transcript ?? $passage->description ?? '',
                    'description' => $passage->description,
                    'questionGroups' => $passage->questionGroups->map(function ($group) {
                        return [
                            'id' => $group->id,
                            'instruction' => $group->instruction,
                            'questions' => $group->questions->map(function ($q) {
                                return [
                                    'id' => $q->id,
                                    'question_type' => $q->question_type,
                                    'question_number' => $q->question_number,
                                    'question_text' => $q->question_text,
                                    'question_data' => $q->question_data,
                                    'points_value' => $q->points_value,
                                    'options' => $q->options->map(fn($o) => [
                                        'id' => $o->id,
                                        'option_key' => $o->option_key,
                                        'option_text' => $o->option_text,
                                    ])->toArray(),
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return response()->json([
                'data' => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'difficulty' => $test->difficulty,
                    'difficulty_level' => $test->difficulty,
                    'timer_type' => $test->timer_mode,
                    'time_limit_seconds' => null,
                    'allow_retake' => $test->allow_repetition,
                    'is_published' => $test->is_published,
                    'passages' => $passages,
                    'created_at' => $test->created_at,
                ]
            ]);
        }

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json(['data' => $task]);
    }

    /**
     * Create a reading submission for a student assignment (handles both task and test based)
     */
    public function createReadingSubmission(Request $request, string $assignmentId): JsonResponse
    {
        $studentId = auth()->id();
        $type = $this->normalizeType($request->input('type', 'reading_task'));

        if (!$this->checkStudentAccess($assignmentId, $type, $studentId)) {
            return response()->json(['message' => 'You do not have access to this assignment'], 403);
        }

        $assignment = \App\Models\Assignment::find($assignmentId);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $submissionService = app(\App\Services\V1\ReadingTest\ReadingSubmissionService::class);
        $attemptNumber = $request->input('attempt_number', 1);

        try {
            if ($assignment->task_id) {
                $task = \App\Models\ReadingTask::findOrFail($assignment->task_id);
                $submission = $submissionService->startReadingTask($task, $studentId, [
                    'assignment_id' => $assignmentId,
                    'attempt_number' => $attemptNumber,
                ]);
            } elseif ($assignment->test_id) {
                $test = \App\Models\Test::findOrFail($assignment->test_id);

                // Check for existing in-progress submission
                $existing = \App\Models\ReadingSubmission::where('test_id', $test->id)
                    ->where('student_id', $studentId)
                    ->where('assignment_id', $assignmentId)
                    ->whereIn('status', ['in_progress'])
                    ->first();

                if ($existing) {
                    $submission = $existing->load('answers');
                } else {
                    $submission = \App\Models\ReadingSubmission::create([
                        'test_id' => $test->id,
                        'assignment_id' => $assignmentId,
                        'student_id' => $studentId,
                        'attempt_number' => $attemptNumber,
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                    $submissionService->initializeAnswersFromTestPublic($submission);
                    $submission->load('answers');
                }
            } else {
                return response()->json(['message' => 'Assignment has no associated task or test'], 422);
            }

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\V1\ReadingTest\ReadingSubmissionResource($submission),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get reading statistics for student dashboard
     */
    public function readingStatistics(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Basic Stats
        $baseSubmissions = DB::table('reading_submissions')
            ->where('student_id', $userId)
            ->whereNotNull('submitted_at');

        $tasksCompleted = (clone $baseSubmissions)->count();
        
        $totalSeconds = (clone $baseSubmissions)->sum('time_taken_seconds');
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        $timeSpent = "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        if ($hours == 0 && $minutes == 0) $timeSpent = "0h";

        $avgScore = (clone $baseSubmissions)->avg('percentage');

        // Recent Submissions
        $recentSubmissions = DB::table('reading_submissions')
            ->leftJoin('reading_tasks', 'reading_submissions.reading_task_id', '=', 'reading_tasks.id')
            ->leftJoin('tests', 'reading_submissions.test_id', '=', 'tests.id')
            ->where('reading_submissions.student_id', $userId)
            ->whereNotNull('reading_submissions.submitted_at')
            ->select(
                DB::raw('COALESCE(reading_tasks.title, tests.title) as task_title'),
                'reading_submissions.percentage as score',
                'reading_submissions.submitted_at'
            )
            ->orderBy('reading_submissions.submitted_at', 'desc')
            ->limit(5)
            ->get();

        // Performance Trend (last 6 months)
        $performanceTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthlyAvg = DB::table('reading_submissions')
                ->where('student_id', $userId)
                ->whereBetween('submitted_at', [$monthStart, $monthEnd])
                ->avg('percentage');

            $performanceTrends[] = [
                'month' => $date->format('M'),
                'avgScore' => round($monthlyAvg ?? 0, 1)
            ];
        }

        // Category Performance
        $categoryPerf = $this->getCategoryPerformance($userId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks_completed' => $tasksCompleted,
                'time_spent' => $timeSpent,
                'average_score' => round($avgScore ?? 0, 1),
                'recent_submissions' => $recentSubmissions,
                'performance_trends' => $performanceTrends,
                'category_performance' => $categoryPerf
            ]
        ]);
    }

    private function getCategoryPerformance($userId)
    {
        // 1. Get legacy test question performance
        $stats = DB::table('reading_question_answers')
            ->join('reading_submissions', 'reading_question_answers.submission_id', '=', 'reading_submissions.id')
            ->join('test_questions', 'reading_question_answers.question_id', '=', 'test_questions.id')
            ->where('reading_submissions.student_id', $userId)
            ->whereNotNull('reading_submissions.submitted_at')
            ->select(
                'test_questions.question_type as category',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN reading_question_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('test_questions.question_type')
            ->get();

        $results = [];
        foreach ($stats as $stat) {
            $categoryName = $this->formatCategoryName($stat->category);
            $results[] = [
                'category' => $categoryName,
                'score' => round(($stat->correct / $stat->total) * 100, 1)
            ];
        }

        if (empty($results)) {
            return [
                ['category' => 'Multiple Choice', 'score' => 0],
                ['category' => 'Identifying Information', 'score' => 0],
                ['category' => 'Matching Headings', 'score' => 0],
                ['category' => 'Sentence Completion', 'score' => 0]
            ];
        }

        return $results;
    }

    private function formatCategoryName($raw)
    {
        $map = [
            'multiple_choice' => 'Multiple Choice',
            'tfng' => 'True/False/Not Given',
            'y n ng' => 'Yes/No/Not Given',
            'matching_heading' => 'Matching Headings',
            'short_answer' => 'Short Answer',
            'completion' => 'Completion',
        ];

        if (isset($map[$raw])) return $map[$raw];

        return ucwords(str_replace(['_', '-'], ' ', $raw));
    }

    /**
     * Get task details by type
     */
    private function getTaskByType(string $assignmentId, string $type)
    {
        $assignment = \App\Models\Assignment::find($assignmentId);
        if (!$assignment) {
            return null;
        }
        
        $task = $assignment->getTask();
        return $assignment;
    }

    /**
     * Normalize task type string
     */
    private function normalizeType(string $type): string
    {
        return match($type) {
            'writing', 'writing_task' => 'writing_task',
            'reading', 'reading_task' => 'reading_task',
            'listening', 'listening_task' => 'listening_task',
            'speaking', 'speaking_task' => 'speaking_task',
            default => $type
        };
    }
}