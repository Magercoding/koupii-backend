<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\Test;
use App\Helpers\Listening\ListeningTestHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListeningTaskService
{
    /**
     * Get listening tasks with filters and pagination
     */
    public function getListeningTasks(array $filters = []): LengthAwarePaginator
    {
        $query = ListeningTask::with(['test', 'audioSegments']);

        // Apply filters
        if (!empty($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }

        if (!empty($filters['task_type'])) {
            $query->where('task_type', $filters['task_type']);
        }

        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('order')->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Create a new listening task
     */
    public function createListeningTask(array $taskData): ListeningTask
    {
        return DB::transaction(function () use ($taskData) {
            $task = ListeningTask::create([
                'id' => Str::uuid(),
                'test_id' => $taskData['test_id'],
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'instructions' => $taskData['instructions'] ?? null,
                'task_type' => $taskData['task_type'] ?? 'listening_comprehension',
                'difficulty_level' => $taskData['difficulty_level'] ?? 'intermediate',
                'points' => $taskData['points'] ?? 0,
                'time_limit' => $taskData['time_limit'] ?? null,
                'order' => $taskData['order'] ?? $this->getNextOrder($taskData['test_id']),
                'metadata' => $taskData['metadata'] ?? null
            ]);

            return $task->load(['test', 'audioSegments']);
        });
    }

    /**
     * Get detailed information about a listening task
     */
    public function getListeningTaskDetails(ListeningTask $task): ListeningTask
    {
        return $task->load([
            'test',
            'audioSegments',
            'test.questions' => function ($query) {
                $query->orderBy('order');
            }
        ]);
    }

    /**
     * Update a listening task
     */
    public function updateListeningTask(ListeningTask $task, array $taskData): ListeningTask
    {
        return DB::transaction(function () use ($task, $taskData) {
            $task->update($taskData);
            return $task->fresh(['test', 'audioSegments']);
        });
    }

    /**
     * Delete a listening task
     */
    public function deleteListeningTask(ListeningTask $task): bool
    {
        return DB::transaction(function () use ($task) {
            // Delete associated audio segments
            $task->audioSegments()->delete();
            
            // Delete associated questions
            $task->test->questions()->delete();
            
            // Delete the task
            return $task->delete();
        });
    }

    /**
     * Get listening tasks for a specific test
     */
    public function getTasksByTest(Test $test): Collection
    {
        return $test->listeningTasks()
            ->with(['audioSegments'])
            ->orderBy('order')
            ->get();
    }

    /**
     * Get next order number for a test
     */
    private function getNextOrder(string $testId): int
    {
        $maxOrder = ListeningTask::where('test_id', $testId)->max('order');
        return ($maxOrder ?? 0) + 1;
    }
}