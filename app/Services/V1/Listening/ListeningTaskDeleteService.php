<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\ListeningSubmission;
use App\Models\ListeningTaskAssignment;
use App\Models\ListeningQuestionAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListeningTaskDeleteService
{
    /**
     * Delete a listening task.
     */
    public function deleteTask(string $taskId): array
    {
        $task = ListeningTask::find($taskId);

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
                $submissionsCount = ListeningSubmission::where('listening_task_id', $task->id)->count();

                if ($submissionsCount > 0) {
                    // If task has submissions, soft delete or archive instead
                    $this->archiveTaskWithSubmissions($task);
                } else {
                    // Safe to hard delete if no submissions
                    $this->hardDeleteTask($task);
                }
            });

            return ['message' => 'Listening task deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Check if user can delete the task.
     */
    private function canDeleteTask(ListeningTask $task): bool
    {
        $user = Auth::user();
        
        // Admin can delete any task
        if ($user->role === 'admin') {
            return true;
        }

        // Task creator can delete their own task
        return $task->creator_id === $user->id;
    }

    /**
     * Delete task-related files.
     */
    private function deleteTaskFiles(ListeningTask $task): void
    {
        // Delete audio files if they exist
        if ($task->audio_segments) {
            foreach ($task->audio_segments as $segment) {
                if (isset($segment['audio_url']) && Storage::exists($segment['audio_url'])) {
                    Storage::delete($segment['audio_url']);
                }
            }
        }

        // Delete any additional files stored in metadata
        if ($task->metadata && isset($task->metadata['files'])) {
            foreach ($task->metadata['files'] as $file) {
                if (Storage::exists($file)) {
                    Storage::delete($file);
                }
            }
        }
    }

    /**
     * Archive task with submissions instead of deleting.
     */
    private function archiveTaskWithSubmissions(ListeningTask $task): void
    {
        // Mark task as archived
        $task->update([
            'is_published' => false,
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => Auth::id(),
        ]);

        // Remove all assignments to prevent new submissions
        ListeningTaskAssignment::where('listening_task_id', $task->id)->delete();
    }

    /**
     * Hard delete task and all related data.
     */
    private function hardDeleteTask(ListeningTask $task): void
    {
        // Delete assignments first
        ListeningTaskAssignment::where('listening_task_id', $task->id)->delete();

        // Delete any orphaned question answers (shouldn't exist without submissions)
        ListeningQuestionAnswer::where('listening_task_id', $task->id)->delete();

        // Delete the task itself
        $task->delete();
    }

    /**
     * Force delete a task (including with submissions).
     */
    public function forceDeleteTask(string $taskId): array
    {
        $task = ListeningTask::find($taskId);

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

                // Delete all submissions and related data
                $submissions = ListeningSubmission::where('listening_task_id', $task->id)->get();
                
                foreach ($submissions as $submission) {
                    // Delete submission files
                    if ($submission->files) {
                        foreach ($submission->files as $file) {
                            if (Storage::exists($file)) {
                                Storage::delete($file);
                            }
                        }
                    }

                    // Delete question answers
                    $submission->answers()->delete();
                    
                    // Delete submission
                    $submission->delete();
                }

                // Delete assignments
                ListeningTaskAssignment::where('listening_task_id', $task->id)->delete();

                // Delete the task
                $task->delete();
            });

            return ['message' => 'Listening task force deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to force delete task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Check if task can be safely deleted (no submissions).
     */
    public function canSafelyDelete(ListeningTask $task): bool
    {
        return ListeningSubmission::where('listening_task_id', $task->id)->count() === 0;
    }

    /**
     * Get task deletion impact info.
     */
    public function getDeletionImpact(string $taskId): array
    {
        $task = ListeningTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $submissionsCount = ListeningSubmission::where('listening_task_id', $task->id)->count();
        $assignmentsCount = ListeningTaskAssignment::where('listening_task_id', $task->id)->count();
        $studentsAffected = ListeningSubmission::where('listening_task_id', $task->id)
            ->distinct('student_id')
            ->count();

        return [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'submissions_count' => $submissionsCount,
            'assignments_count' => $assignmentsCount,
            'students_affected' => $studentsAffected,
            'can_safely_delete' => $submissionsCount === 0,
            'will_be_archived' => $submissionsCount > 0,
        ];
    }
}