<?php

namespace App\Http\Controllers\V1\StudentDashboard;

use App\Http\Controllers\Controller;
use App\Models\StudentAssignment;
use App\Models\WritingTaskAssignment;
use App\Models\ReadingTaskAssignment;
use App\Models\ListeningTaskAssignment;
use App\Models\SpeakingTaskAssignment;
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

            // Get Writing Task Assignments
            $writingAssignments = WritingTaskAssignment::where('class_id', $classId)
                ->with(['writingTask', 'studentAssignments' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get()
                ->map(function($assignment) use ($className) {
                    $studentAssignment = $assignment->studentAssignments->first();
                    return [
                        'id' => $assignment->id,
                        'type' => 'writing_task',
                        'title' => $assignment->writingTask->title,
                        'description' => $assignment->writingTask->description,
                        'class_name' => $className,
                        'due_date' => $assignment->due_date,
                        'assigned_date' => $assignment->created_at,
                        'status' => $studentAssignment?->status ?? 'pending',
                        'score' => $studentAssignment?->score,
                        'completion_date' => $studentAssignment?->completed_at,
                        'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                        'max_attempts' => $assignment->max_attempts,
                        'word_limit' => $assignment->writingTask->word_limit,
                        'difficulty' => $assignment->writingTask->difficulty_level,
                    ];
                });

            // Get Reading Task Assignments
            $readingAssignments = ReadingTaskAssignment::where('class_id', $classId)
                ->with(['readingTask', 'studentAssignments' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get()
                ->map(function($assignment) use ($className) {
                    $studentAssignment = $assignment->studentAssignments->first();
                    return [
                        'id' => $assignment->id,
                        'type' => 'reading_task',
                        'title' => $assignment->readingTask->title,
                        'description' => $assignment->readingTask->description,
                        'class_name' => $className,
                        'due_date' => $assignment->due_date,
                        'assigned_date' => $assignment->created_at,
                        'status' => $studentAssignment?->status ?? 'pending',
                        'score' => $studentAssignment?->score,
                        'completion_date' => $studentAssignment?->completed_at,
                        'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                        'max_attempts' => $assignment->max_attempts,
                        'time_limit' => $assignment->readingTask->time_limit_seconds,
                        'difficulty' => $assignment->readingTask->difficulty_level,
                    ];
                });

            // Get Listening Task Assignments
            $listeningAssignments = ListeningTaskAssignment::where('class_id', $classId)
                ->with(['listeningTask', 'studentAssignments' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get()
                ->map(function($assignment) use ($className) {
                    $studentAssignment = $assignment->studentAssignments->first();
                    return [
                        'id' => $assignment->id,
                        'type' => 'listening_task',
                        'title' => $assignment->listeningTask->title,
                        'description' => $assignment->listeningTask->description,
                        'class_name' => $className,
                        'due_date' => $assignment->due_date,
                        'assigned_date' => $assignment->created_at,
                        'status' => $studentAssignment?->status ?? 'pending',
                        'score' => $studentAssignment?->score,
                        'completion_date' => $studentAssignment?->completed_at,
                        'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                        'max_attempts' => $assignment->max_attempts,
                        'time_limit' => $assignment->listeningTask->time_limit_seconds,
                        'difficulty' => $assignment->listeningTask->difficulty_level,
                    ];
                });

            // Get Speaking Task Assignments
            $speakingAssignments = SpeakingTaskAssignment::where('class_id', $classId)
                ->with(['speakingTask', 'studentAssignments' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get()
                ->map(function($assignment) use ($className) {
                    $studentAssignment = $assignment->studentAssignments->first();
                    return [
                        'id' => $assignment->id,
                        'type' => 'speaking_task',
                        'title' => $assignment->speakingTask->title,
                        'description' => $assignment->speakingTask->description,
                        'class_name' => $className,
                        'due_date' => $assignment->due_date,
                        'assigned_date' => $assignment->created_at,
                        'status' => $studentAssignment?->status ?? 'pending',
                        'score' => $studentAssignment?->score,
                        'completion_date' => $studentAssignment?->completed_at,
                        'attempt_count' => $studentAssignment?->attempt_count ?? 0,
                        'max_attempts' => $assignment->max_attempts,
                        'time_limit' => $assignment->speakingTask->time_limit_seconds,
                        'difficulty' => $assignment->speakingTask->difficulty_level,
                    ];
                });

            // Merge all assignments
            $assignments = $assignments->merge($writingAssignments)
                ->merge($readingAssignments)
                ->merge($listeningAssignments)
                ->merge($speakingAssignments);
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
            'status' => 'started',
            'attempt_count' => 0
        ]);

        return response()->json([
            'message' => 'Assignment details retrieved successfully',
            'data' => [
                'assignment' => $assignmentData,
                'student_progress' => [
                    'status' => $studentAssignment->status,
                    'attempt_count' => $studentAssignment->attempt_count,
                    'score' => $studentAssignment->score,
                    'started_at' => $studentAssignment->started_at,
                    'completed_at' => $studentAssignment->completed_at
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
            'status' => 'in_progress',
            'attempt_count' => 1,
            'started_at' => now()
        ]);

        // If already exists, update status and increment attempt if needed
        if (!$studentAssignment->wasRecentlyCreated && $studentAssignment->status !== 'in_progress') {
            $studentAssignment->update([
                'status' => 'in_progress',
                'attempt_count' => $studentAssignment->attempt_count + 1,
                'started_at' => now()
            ]);
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

        $studentAssignment = StudentAssignment::where([
            'student_id' => $studentId,
            'assignment_id' => $assignmentId,
            'assignment_type' => $type,
            'status' => 'in_progress'
        ])->first();

        if (!$studentAssignment) {
            return response()->json([
                'message' => 'No active assignment found'
            ], 404);
        }

        // Update assignment as completed
        $studentAssignment->update([
            'status' => 'submitted',
            'completed_at' => now(),
            'submission_data' => $request->input('submission_data', [])
        ]);

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'data' => [
                'student_assignment_id' => $studentAssignment->id,
                'completed_at' => $studentAssignment->completed_at,
                'submission_status' => 'submitted'
            ]
        ]);
    }

    /**
     * Check if student has access to assignment
     */
    private function checkStudentAccess(string $assignmentId, string $type, string $studentId): bool
    {
        $modelClass = match($type) {
            'writing_task' => WritingTaskAssignment::class,
            'reading_task' => ReadingTaskAssignment::class,
            'listening_task' => ListeningTaskAssignment::class,
            'speaking_task' => SpeakingTaskAssignment::class,
            default => null
        };

        if (!$modelClass) {
            return false;
        }

        $assignment = $modelClass::find($assignmentId);
        if (!$assignment) {
            return false;
        }

        // Check if student is enrolled in the class
        return ClassEnrollment::where([
            'student_id' => $studentId,
            'class_id' => $assignment->class_id,
            'status' => 'active'
        ])->exists();
    }

    /**
     * Get task details by type
     */
    private function getTaskByType(string $assignmentId, string $type)
    {
        return match($type) {
            'writing_task' => WritingTaskAssignment::with('writingTask')->find($assignmentId),
            'reading_task' => ReadingTaskAssignment::with('readingTask')->find($assignmentId),
            'listening_task' => ListeningTaskAssignment::with('listeningTask')->find($assignmentId),
            'speaking_task' => SpeakingTaskAssignment::with('speakingTask')->find($assignmentId),
            default => null
        };
    }
}