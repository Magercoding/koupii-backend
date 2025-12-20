<?php

namespace App\Services\V1\WritingTest;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingTestService
{
    /**
     * Create a new writing test.
     */
    public function createTest(array $data): Test
    {
        return DB::transaction(function () use ($data) {
            $test = Test::create([
                'id' => Str::uuid(),
                'creator_id' => Auth::id(),
                'type' => 'writing',
                'test_type' => $data['test_type'] ?? 'general',
                'difficulty' => $data['difficulty'] ?? 'intermediate',
                'title' => $data['title'],
                'description' => $data['description'],
                'timer_mode' => $data['timer_mode'] ?? 'none',
                'timer_settings' => $data['timer_settings'] ?? [],
                'allow_repetition' => $data['allow_repetition'] ?? false,
                'max_repetition_count' => $data['max_repetition_count'] ?? 1,
                'is_public' => $data['is_public'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'settings' => $data['settings'] ?? [],
            ]);

            return $test->load('creator');
        });
    }

    /**
     * Update an existing writing test.
     */
    public function updateTest(Test $test, array $data): Test
    {
        return DB::transaction(function () use ($test, $data) {
            $test->update([
                'title' => $data['title'] ?? $test->title,
                'description' => $data['description'] ?? $test->description,
                'test_type' => $data['test_type'] ?? $test->test_type,
                'difficulty' => $data['difficulty'] ?? $test->difficulty,
                'timer_mode' => $data['timer_mode'] ?? $test->timer_mode,
                'timer_settings' => $data['timer_settings'] ?? $test->timer_settings,
                'allow_repetition' => $data['allow_repetition'] ?? $test->allow_repetition,
                'max_repetition_count' => $data['max_repetition_count'] ?? $test->max_repetition_count,
                'is_public' => $data['is_public'] ?? $test->is_public,
                'is_published' => $data['is_published'] ?? $test->is_published,
                'settings' => $data['settings'] ?? $test->settings,
            ]);

            return $test->load('creator');
        });
    }

    /**
     * Duplicate a writing test.
     */
    public function duplicateTest(Test $originalTest, array $overrides = []): Test
    {
        return DB::transaction(function () use ($originalTest, $overrides) {
            $testData = $originalTest->toArray();

            // Remove fields that shouldn't be duplicated
            unset($testData['id'], $testData['created_at'], $testData['updated_at']);

            // Set new values
            $testData['creator_id'] = Auth::id();
            $testData['title'] = $overrides['title'] ?? $testData['title'] . ' (Copy)';
            $testData['is_published'] = false; // Always start as unpublished

            // Apply any other overrides
            $testData = array_merge($testData, $overrides);

            return $this->createTest($testData);
        });
    }

    /**
     * Get test statistics.
     */
    public function getTestStatistics(Test $test): array
    {
        // Since this is a template test, we might not have direct attempts
        // You could track usage through WritingTasks that reference this test

        return [
            'total_uses' => 0, // How many times this test template was used
            'created_tasks' => 0, // How many tasks were created from this template
            'average_completion_rate' => 0,
            'last_used' => null,
        ];
    }

    /**
     * Create a writing task from this test template.
     */
    public function createTaskFromTest(Test $test, array $taskData): \App\Models\WritingTask
    {
        $taskService = new \App\Services\V1\WritingTask\WritingTaskService();

        // Merge test settings with task-specific data
        $mergedData = array_merge([
            'title' => $test->title,
            'description' => $test->description,
            'instructions' => $taskData['instructions'] ?? null,
            'timer_type' => $this->convertTimerMode($test->timer_mode),
            'time_limit_seconds' => $this->extractTimeLimit($test->timer_settings),
        ], $taskData);

        return $taskService->create($mergedData, request());
    }

    /**
     * Convert timer mode from test to task format.
     */
    private function convertTimerMode(string $timerMode): string
    {
        return match ($timerMode) {
            'test' => 'countdown',
            'none' => 'none',
            default => 'countup',
        };
    }

    /**
     * Extract time limit from timer settings.
     */
    private function extractTimeLimit(array $timerSettings): ?int
    {
        if (isset($timerSettings['test_time'])) {
            return $timerSettings['test_time'] * 60; // Convert minutes to seconds
        }

        return null;
    }

    /**
     * Archive/unarchive a test.
     */
    public function toggleArchive(Test $test): Test
    {
        $test->update([
            'is_archived' => !($test->is_archived ?? false),
            'archived_at' => ($test->is_archived ?? false) ? null : now(),
        ]);

        return $test;
    }

    /**
     * Publish/unpublish a test.
     */
    public function togglePublish(Test $test): Test
    {
        $test->update([
            'is_published' => !$test->is_published,
            'published_at' => $test->is_published ? null : now(),
        ]);

        return $test;
    }

    /**
     * Get all published writing tests for selection.
     */
    public function getPublishedTests(): \Illuminate\Database\Eloquent\Collection
    {
        return Test::where('type', 'writing')
            ->where('is_published', true)
            ->where('is_public', true)
            ->with('creator')
            ->orderBy('title')
            ->get();
    }

    /**
     * Search tests by criteria.
     */
    public function searchTests(array $criteria): \Illuminate\Database\Eloquent\Collection
    {
        $query = Test::where('type', 'writing');

        if (isset($criteria['title'])) {
            $query->where('title', 'like', '%' . $criteria['title'] . '%');
        }

        if (isset($criteria['difficulty'])) {
            $query->where('difficulty', $criteria['difficulty']);
        }

        if (isset($criteria['test_type'])) {
            $query->where('test_type', $criteria['test_type']);
        }

        if (isset($criteria['creator_id'])) {
            $query->where('creator_id', $criteria['creator_id']);
        }

        if (isset($criteria['is_published'])) {
            $query->where('is_published', $criteria['is_published']);
        }

        return $query->with('creator')
            ->orderBy('title')
            ->get();
    }
}