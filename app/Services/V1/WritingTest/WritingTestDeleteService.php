<?php

namespace App\Services\V1\WritingTest;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use App\Models\WritingSubmission;
use App\Models\WritingReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WritingTestDeleteService
{
    /**
     * Delete a writing task and all related data.
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
                // Delete all files from submissions
                $submissions = WritingSubmission::where('writing_task_id', $task->id)->get();
                foreach ($submissions as $submission) {
                    $this->deleteSubmissionFiles($submission);
                }

                // Delete reviews (cascade should handle this, but explicit for safety)
                WritingReview::whereIn('submission_id', $submissions->pluck('id'))->delete();

                // Delete submissions
                WritingSubmission::where('writing_task_id', $task->id)->delete();

                // Delete assignments
                WritingTaskAssignment::where('writing_task_id', $task->id)->delete();

                // Delete task files
                $this->deleteTaskFiles($task);

                // Delete the task
                $task->delete();
            });

            return ['message' => 'Writing task deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete task: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Delete a specific submission.
     */
    public function deleteSubmission(string $submissionId): array
    {
        $submission = WritingSubmission::find($submissionId);

        if (!$submission) {
            return ['error' => 'Submission not found', 'status' => 404];
        }

        if (!$this->canDeleteSubmission($submission)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            DB::transaction(function () use ($submission) {
                // Delete associated files
                $this->deleteSubmissionFiles($submission);

                // Delete review if exists
                if ($submission->review) {
                    $submission->review->delete();
                }

                // Delete the submission
                $submission->delete();
            });

            return ['message' => 'Submission deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete submission: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Delete assignment (remove task from classroom).
     */
    public function deleteAssignment(string $assignmentId): array
    {
        $assignment = WritingTaskAssignment::find($assignmentId);

        if (!$assignment) {
            return ['error' => 'Assignment not found', 'status' => 404];
        }

        if (!$this->canDeleteAssignment($assignment)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            $assignment->delete();
            return ['message' => 'Task removed from classroom successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to remove assignment: ' . $e->getMessage(), 'status' => 500];
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
     * Check if user can delete the submission.
     */
    protected function canDeleteSubmission(WritingSubmission $submission): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        // Teacher can delete submissions for their tasks
        if ($user->role === 'teacher' && $submission->writingTask->creator_id === $user->id) {
            return true;
        }

        // Student can delete their own submissions (only if not yet reviewed)
        return $submission->student_id === $user->id && $submission->status === 'to_do';
    }

    /**
     * Check if user can delete the assignment.
     */
    protected function canDeleteAssignment(WritingTaskAssignment $assignment): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        return $assignment->writingTask->creator_id === $user->id;
    }

    /**
     * Delete files associated with a submission.
     */
    protected function deleteSubmissionFiles(WritingSubmission $submission): void
    {
        if ($submission->files) {
            $files = is_array($submission->files) ? $submission->files : json_decode($submission->files, true);

            if (is_array($files)) {
                foreach ($files as $file) {
                    $filePath = is_array($file) ? $file['path'] : $file;
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }
        }
    }

    /**
     * Delete files associated with a task.
     */
    protected function deleteTaskFiles(WritingTask $task): void
    {
        // Delete any task-level attachments or sample files
        // This depends on your task structure - add any file fields here
    }

    /**
     * Bulk delete submissions by status.
     */
    public function bulkDeleteSubmissions(string $taskId, array $statuses = []): array
    {
        $task = WritingTask::find($taskId);

        if (!$task) {
            return ['error' => 'Task not found', 'status' => 404];
        }

        if (!$this->canDeleteTask($task)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            $query = WritingSubmission::where('writing_task_id', $taskId);

            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }

            $submissions = $query->get();
            $count = $submissions->count();

            DB::transaction(function () use ($submissions) {
                foreach ($submissions as $submission) {
                    $this->deleteSubmissionFiles($submission);
                    if ($submission->review) {
                        $submission->review->delete();
                    }
                    $submission->delete();
                }
            });

            return ['message' => "Deleted {$count} submissions successfully", 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete submissions: ' . $e->getMessage(), 'status' => 500];
        }
    }
}