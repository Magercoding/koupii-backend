<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use App\Models\WritingSubmission;
use Illuminate\Support\Facades\Auth;

class WritingDashboardService
{
    /**
     * Get student dashboard data.
     */
    public function getStudentDashboard(): array
    {
        $studentId = Auth::id();

        // Get all assigned tasks for student through their classrooms
        $assignments = WritingTaskAssignment::whereHas('writingTask', function ($query) {
            $query->where('is_published', true);
        })
            ->whereHas('classroom.students', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with([
                'writingTask',
                'writingTask.submissions' => function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)->latest('attempt_number');
                },
                'writingTask.submissions.review'
            ])
            ->get();

        return $assignments->map(function ($assignment) {
            $task = $assignment->writingTask;
            $submission = $task->submissions->first(); // Latest submission for this student

            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date,
                'status' => $submission ? $submission->status : 'to_do',
                'score' => $submission && $submission->review ? $submission->review->score : null,
                'attempt_number' => $submission ? $submission->attempt_number : 0,
                'submitted_at' => $submission ? $submission->submitted_at : null,
                'can_retake' => $task->allow_retake &&
                    $submission &&
                    $submission->status === 'reviewed' &&
                    (!$task->max_retake_attempts || $submission->attempt_number < $task->max_retake_attempts),
                'retake_options' => $task->retake_options,
                'has_feedback' => $submission && $submission->review ? true : false,
                // Due status
                'is_overdue' => $task->due_date && now()->gt($task->due_date),
                'time_remaining' => $task->due_date ? $task->due_date->diffForHumans() : null,
                // Status display helpers
                'status_display' => [
                    'text' => $this->getStatusDisplayText($submission),
                    'color' => $this->getStatusColor($submission),
                    'icon' => $this->getStatusIcon($submission),
                ],
            ];
        })->toArray();
    }

    /**
     * Get teacher dashboard data.
     */
    public function getTeacherDashboard(): array
    {
        $teacherId = Auth::id();

        $tasks = WritingTask::where('creator_id', $teacherId)
            ->with(['assignments.classroom', 'submissions.student', 'submissions.review'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $tasks->map(function ($task) {
            $submissions = $task->submissions;
            $totalSubmissions = $submissions->count();
            $reviewedSubmissions = $submissions->where('status', 'reviewed')->count();
            $pendingSubmissions = $submissions->where('status', 'submitted')->count();

            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'created_at' => $task->created_at,
                'due_date' => $task->due_date,
                'is_published' => $task->is_published,
                'assigned_classes' => $task->assignments->count(),
                'total_submissions' => $totalSubmissions,
                'pending_reviews' => $pendingSubmissions,
                'reviewed_submissions' => $reviewedSubmissions,
                'average_score' => $submissions->whereNotNull('review.score')->avg('review.score'),
                'classrooms' => $task->assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->classroom->id,
                        'name' => $assignment->classroom->name,
                        'assigned_at' => $assignment->assigned_at,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get admin dashboard data.
     */
    public function getAdminDashboard(): array
    {
        $totalTasks = WritingTask::count();
        $publishedTasks = WritingTask::where('is_published', true)->count();
        $totalSubmissions = WritingSubmission::count();
        $pendingReviews = WritingSubmission::where('status', 'submitted')->count();
        $completedReviews = WritingSubmission::where('status', 'reviewed')->count();

        // Get recent tasks
        $recentTasks = WritingTask::with(['creator', 'assignments.classroom'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get top teachers by task creation
        $topTeachers = WritingTask::selectRaw('creator_id, COUNT(*) as task_count')
            ->with('creator')
            ->groupBy('creator_id')
            ->orderBy('task_count', 'desc')
            ->limit(5)
            ->get();

        return [
            'statistics' => [
                'total_tasks' => $totalTasks,
                'published_tasks' => $publishedTasks,
                'total_submissions' => $totalSubmissions,
                'pending_reviews' => $pendingReviews,
                'completed_reviews' => $completedReviews,
                'average_score' => WritingSubmission::whereHas('review')->avg('review.score'),
            ],
            'recent_tasks' => $recentTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'creator_name' => $task->creator->name,
                    'created_at' => $task->created_at,
                    'is_published' => $task->is_published,
                    'assignments_count' => $task->assignments->count(),
                ];
            }),
            'top_teachers' => $topTeachers->map(function ($teacher) {
                return [
                    'teacher_id' => $teacher->creator_id,
                    'teacher_name' => $teacher->creator->name,
                    'task_count' => $teacher->task_count,
                ];
            }),
        ];
    }

    /**
     * Get status display text for UI.
     */
    private function getStatusDisplayText($submission): string
    {
        if (!$submission)
            return 'To Do';

        return match ($submission->status) {
            'to_do' => 'In Progress',
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'done' => 'Done',
            default => 'To Do',
        };
    }

    /**
     * Get status color for UI.
     */
    private function getStatusColor($submission): string
    {
        if (!$submission)
            return 'gray';

        return match ($submission->status) {
            'to_do' => 'yellow',
            'submitted' => 'blue',
            'reviewed' => 'green',
            'done' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI.
     */
    private function getStatusIcon($submission): string
    {
        if (!$submission)
            return 'clock';

        return match ($submission->status) {
            'to_do' => 'edit',
            'submitted' => 'upload',
            'reviewed' => 'check-circle',
            'done' => 'check-double',
            default => 'clock',
        };
    }
}