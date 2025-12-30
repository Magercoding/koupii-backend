<?php

namespace App\Services\V1\ReadingTask;

use App\Models\ReadingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReadingTaskDeleteService
{
    /**
     * Delete a reading task and all related data
     */
    public function delete(ReadingTask $task): bool
    {
        return DB::transaction(function () use ($task) {
            // Delete file uploads
            $this->deleteTaskFiles($task);

            // Delete related assignments
            $task->assignments()->delete();

            // Delete related submissions
            $task->submissions()->delete();

            // Delete the task itself
            return $task->delete();
        });
    }

    /**
     * Delete files associated with the task
     */
    private function deleteTaskFiles(ReadingTask $task): void
    {
        try {
            // Delete passage images
            if ($task->passage_images && is_array($task->passage_images)) {
                foreach ($task->passage_images as $imageData) {
                    if (isset($imageData['file_path']) && Storage::exists($imageData['file_path'])) {
                        Storage::delete($imageData['file_path']);
                    }
                }
            }

            // If there are reference materials in the future
            // Add similar logic here for reference materials
        } catch (\Exception $e) {
            // Log the error but don't fail the deletion
            \Log::warning('Failed to delete reading task files: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete a reading task (mark as unpublished instead of deleting)
     */
    public function softDelete(ReadingTask $task): bool
    {
        return $task->update([
            'is_published' => false,
            'title' => '[DELETED] ' . $task->title
        ]);
    }

    /**
     * Restore a soft-deleted reading task
     */
    public function restore(ReadingTask $task): bool
    {
        $restoredTitle = str_replace('[DELETED] ', '', $task->title);
        
        return $task->update([
            'is_published' => false, // Keep unpublished for review
            'title' => $restoredTitle
        ]);
    }
}