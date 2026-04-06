<?php

namespace App\Http\Controllers\V1\StudentDashboard;

use App\Http\Controllers\Controller;
use App\Models\StudentAssignment;
use App\Models\ClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentDashboardController extends Controller
{
    /**
     * Get student dashboard with assignments from all enrolled classes
     */
    public function dashboard(): JsonResponse
    {
        $studentId = auth()->id();
        
        // Get all classes the student is enrolled in
        $enrolledClasses = ClassEnrollment::where('student_id', $studentId)
            ->with('class')
            ->get();

        $assignments = collect();

        foreach ($enrolledClasses as $enrollment) {
            $classId = $enrollment->class_id;
            $className = $enrollment->class->name;

            // Get assignments from the unified Assignment model
            $classAssignments = \App\Models\Assignment::where('class_id', $classId)
                ->with(['studentAssignments' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get()
                ->map(function($assignment) use ($className) {
                    $studentAssignment = $assignment->studentAssignments->first();
                    $task = $assignment->getTask();
                    
                    return [
                        'id' => $assignment->id,
                        'type' => $assignment->type ?? $assignment->task_type,
                        'title' => $assignment->title ?? $task?->title,
                        'description' => $assignment->description ?? $task?->description,
                        'class_name' => $className,
                        'due_date' => $assignment->due_date,
                        'assigned_date' => $assignment->created_at,
                        'status' => $studentAssignment?->status ?? 'pending',
                        'score' => $studentAssignment?->score,
                        'completion_date' => $studentAssignment?->completed_at,
                        'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                        'max_attempts' => $assignment->max_attempts,
                        'time_limit' => $task->time_limit_seconds ?? null,
                        'word_limit' => $task->word_limit ?? $task->min_word_count ?? null,
                        'difficulty' => $task->difficulty ?? $task->difficulty_level ?? null,
                    ];
                });

            // Merge all assignments
            $assignments = $assignments->merge($classAssignments);
        }

        // Sort assignments by due date
        $assignments = $assignments->sortBy('due_date');

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'student_name' => auth()->user()->name,
                'enrolled_classes' => $enrolledClasses->count(),
                'total_assignments' => $assignments->count(),
                'pending_assignments' => $assignments->where('status', 'pending')->count(),
                'completed_assignments' => $assignments->where('status', 'completed')->count(),
                'overdue_assignments' => $assignments->where('due_date', '<', now())
                    ->where('status', '!=', 'completed')->count(),
                'assignments' => $assignments->values()
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

        // Get or create student assignment record
        $studentAssignment = StudentAssignment::firstOrCreate([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
            'assignment_type' => $type
        ], [
            'status' => StudentAssignment::STATUS_NOT_STARTED,
            'attempt_count' => 0
        ]);

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

        // Get or create student assignment
        $studentAssignment = StudentAssignment::firstOrCreate([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
            'assignment_type' => $type
        ], [
            'status' => StudentAssignment::STATUS_IN_PROGRESS,
            'attempt_count' => 0,
            'started_at' => now()
        ]);

        // If already exists, just update status to in_progress (don't increment attempts here;
        // the specific submission service like ListeningSubmissionService handles attempt tracking)
        if (!$studentAssignment->wasRecentlyCreated && $studentAssignment->status !== StudentAssignment::STATUS_IN_PROGRESS) {
            $updateData = [
                'status' => StudentAssignment::STATUS_IN_PROGRESS,
                'last_activity_at' => now(),
            ];

            // If it was already finished, increment attempt count for a retake
            if (in_array($studentAssignment->status, [StudentAssignment::STATUS_SUBMITTED, StudentAssignment::STATUS_COMPLETED])) {
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