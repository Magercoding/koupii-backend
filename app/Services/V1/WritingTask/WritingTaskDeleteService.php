<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingSubmission;
use App\Models\WritingTaskAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WritingTaskDeleteService
{
    /**
     * Delete a writing task.
     */
    public function deleteTask(string $taskId): array
    {
        $task = WritingTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found', 'status' => 404];
        }

        if (!$this->canDeleteTask($task)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            DB::transaction(function () use ($task) {
                // Delete task-related files
                $this->deleteTaskFiles($task);

                // Check if task has submissions
                $submissionsCount = WritingSubmission::where('writing_task_id', $task->id)->count();

                if ($submissionsCount > 0) {
                    // If task has submissions, soft delete or archive instead
                    $this->archiveTaskWithSubmissions($task);
                } else {
                    // Safe to hard delete if no submissions
                    $this->hardDeleteTask($task);
                }
            });

            return ['message' => 'Writing task deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Check if user can delete the task.
     */
    protected function canDeleteTask(WritingTask $task): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        return $task->creator_id === $user->id;
    }

    /**
     * Archive task that has submissions (soft delete).
     */
    protected function archiveTaskWithSubmissions(WritingTask $task): void
    {
        // Mark task as archived instead of deleting
        $task->update([
            'is_published' => false,
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => Auth::id(),
        ]);

        // Remove assignments (students can't access anymore)
        WritingTaskAssignment::where('writing_task_id', $task->id)->delete();
    }

    /**
     * Hard delete task with no submissions.
     */
    protected function hardDeleteTask(WritingTask $task): void
    {
        // Delete assignments first
        WritingTaskAssignment::where('writing_task_id', $task->id)->delete();

        // Delete the task
        $task->delete();
    }

    /**
     * Delete files associated with the task.
     */
    protected function deleteTaskFiles(WritingTask $task): void
    {
        // Delete task attachments if any
        if ($task->sample_answer && $this->isFilePath($task->sample_answer)) {
            $this->deleteFile($task->sample_answer);
        }

        // Delete any file attachments stored in task data
        if ($task->files && is_array($task->files)) {
            foreach ($task->files as $file) {
                if (isset($file['path'])) {
                    $this->deleteFile($file['path']);
                }
            }
        }

        // Delete submission files related to this task
        $submissions = WritingSubmission::where('writing_task_id', $task->id)->get();
        foreach ($submissions as $submission) {
            if ($submission->files && is_array($submission->files)) {
                foreach ($submission->files as $file) {
                    if (isset($file['path'])) {
                        $this->deleteFile($file['path']);
                    }
                }
            }
        }
    }

    /**
     * Delete a single file from storage.
     */
    protected function deleteFile(string $path): void
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the deletion
            \Log::error('Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Check if string is a file path.
     */
    protected function isFilePath(string $content): bool
    {
        // Simple check - you can make this more sophisticated
        return str_contains($content, '/') &&
            (str_contains($content, '.pdf') ||
                str_contains($content, '.doc') ||
                str_contains($content, '.txt'));
    }

    /**
     * Force delete a task (admin only).
     */
    public function forceDeleteTask(string $taskId): array
    {
        if (Auth::user()->role !== 'admin') {
            return ['error' => 'Unauthorized - Admin only', 'status' => 403];
        }

        $task = WritingTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found', 'status' => 404];
        }

        try {
            DB::transaction(function () use ($task) {
                // Delete all related data
                $this->deleteTaskFiles($task);

                // Delete all submissions and reviews
                $submissions = WritingSubmission::where('writing_task_id', $task->id)->get();
                foreach ($submissions as $submission) {
                    // Delete reviews
                    $submission->review()?->delete();
                    // Delete submission
                    $submission->delete();
                }

                // Delete assignments
                WritingTaskAssignment::where('writing_task_id', $task->id)->delete();

                // Delete the task
                $task->delete();
            });

            return ['message' => 'Task force deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to force delete task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Restore archived task.
     */
    public function restoreTask(string $taskId): array
    {
        $task = WritingTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found', 'status' => 404];
        }

        if (!$this->canDeleteTask($task)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        if (!($task->is_archived ?? false)) {
            return ['error' => 'Task is not archived', 'status' => 400];
        }

        try {
            $task->update([
                'is_archived' => false,
                'archived_at' => null,
                'archived_by' => null,
            ]);

            return ['message' => 'Task restored successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to restore task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Get deletion impact analysis.
     */
    public function getDeletionImpact(string $taskId): array
    {
        $task = WritingTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $submissionsCount = WritingSubmission::where('writing_task_id', $task->id)->count();
        $assignmentsCount = WritingTaskAssignment::where('writing_task_id', $task->id)->count();
        $reviewsCount = WritingSubmission::where('writing_task_id', $task->id)
            ->whereHas('review')->count();

        return [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'impact' => [
                'submissions_count' => $submissionsCount,
                'assignments_count' => $assignmentsCount,
                'reviews_count' => $reviewsCount,
                'can_hard_delete' => $submissionsCount === 0,
                'deletion_type' => $submissionsCount > 0 ? 'archive' : 'delete',
                'warning' => $submissionsCount > 0 ? 'Task has submissions and will be archived instead of deleted' : null,
            ]
        ];
    }
}