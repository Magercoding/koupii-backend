<?php

namespace App\Services\V1\WritingTest;

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\WritingTask;
class WritingTestDeleteService
{
    /**
     * Delete a writing test.
     */
    public function deleteTest(string $testId): array
    {
        $test = Test::find($testId);

        if (!$test) {
            return ['error' => 'Test not found', 'status' => 404];
        }

        if (!$this->canDeleteTest($test)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            DB::transaction(function () use ($test) {
                // Delete any test-related files
                $this->deleteTestFiles($test);

                // Check if any WritingTasks reference this test
                $referencingTasks = WritingTask::where('test_template_id', $test->id)->count();

                if ($referencingTasks > 0) {
                    // Don't delete if tasks are using this template
                    throw new \Exception('Cannot delete test template that is being used by active tasks');
                }

                // Delete the test
                $test->delete();
            });

            return ['message' => 'Writing test deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete test: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Check if user can delete the test.
     */
    protected function canDeleteTest(Test $test): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return true;
        }

        return $test->creator_id === $user->id;
    }

    /**
     * Delete files associated with the test.
     */
    protected function deleteTestFiles(Test $test): void
    {
        // Delete test cover image if exists
        if (isset($test->settings['cover_image']) && $test->settings['cover_image']) {
            $this->deleteFile($test->settings['cover_image']);
        }

        // Delete any test-level attachments
        if (isset($test->settings['attachments']) && is_array($test->settings['attachments'])) {
            foreach ($test->settings['attachments'] as $attachment) {
                if (isset($attachment['path'])) {
                    $this->deleteFile($attachment['path']);
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
     * Archive a test instead of deleting.
     */
    public function archiveTest(string $testId): array
    {
        $test = Test::find($testId);

        if (!$test) {
            return ['error' => 'Test not found', 'status' => 404];
        }

        if (!$this->canDeleteTest($test)) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            $test->update([
                'is_published' => false,
                'is_archived' => true,
                'archived_at' => now(),
            ]);

            return ['message' => 'Test archived successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to archive test: ' . $e->getMessage(), 'status' => 500];
        }
    }
}