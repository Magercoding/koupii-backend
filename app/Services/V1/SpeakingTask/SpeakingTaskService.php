<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\SpeakingTask;
use App\Models\SpeakingTaskAssignment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class SpeakingTaskService
{
    /**
     * Get speaking tasks with filters and role-based access
     */
    public function getSpeakingTasks(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();
        $query = SpeakingTask::with(['creator']);

        // Role-based access
        if ($user->role === 'student') {
            $query->published()
                ->whereHas('assignments.classroom', function ($q) use ($user) {
                    $q->whereHas('enrollments', function ($e) use ($user) {
                        $e->where('student_id', $user->id)->where('status', 'active');
                    });
                });
        } elseif ($user->role !== 'admin') {
            // Teachers see only their own tasks
            $query->where('created_by', $user->id);
        }

        // Apply filters
        return $query->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($filters['difficulty'] ?? null, function ($q, $difficulty) {
                $q->where('difficulty_level', $difficulty);
            })
            ->when(isset($filters['is_published']), function ($q) use ($filters) {
                $q->where('is_published', $filters['is_published']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new speaking task
     */
    public function createSpeakingTask(array $data): SpeakingTask
    {
        return DB::transaction(function () use ($data) {
            $task = SpeakingTask::create([
                'title'              => $data['title'],
                'description'        => $data['description'] ?? null,
                'instructions'       => $data['instructions'] ?? null,
                'difficulty_level'   => $data['difficulty'] ?? $data['difficulty_level'] ?? 'beginner',
                'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
                'topic'              => $data['topic'] ?? null,
                'situation_context'  => $data['situation_context'] ?? null,
                'questions'          => $data['questions'] ?? $data['sections'] ?? null,
                'sample_audio'       => $data['sample_audio'] ?? null,
                'rubric'             => $data['rubric'] ?? null,
                'is_published'       => $data['is_published'] ?? false,
                'created_by'         => Auth::id(),
            ]);

            return $task->fresh(['creator']);
        });
    }

    /**
     * Update an existing speaking task
     */
    public function updateSpeakingTask(SpeakingTask $task, array $data): SpeakingTask
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update(array_filter([
                'title'              => $data['title'] ?? $task->title,
                'description'        => $data['description'] ?? $task->description,
                'instructions'       => $data['instructions'] ?? $task->instructions,
                'difficulty_level'   => $data['difficulty'] ?? $data['difficulty_level'] ?? $task->difficulty_level,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? $task->time_limit_seconds,
                'topic'              => $data['topic'] ?? $task->topic,
                'situation_context'  => $data['situation_context'] ?? $task->situation_context,
                'questions'          => $data['questions'] ?? $data['sections'] ?? $task->questions,
                'sample_audio'       => $data['sample_audio'] ?? $task->sample_audio,
                'rubric'             => $data['rubric'] ?? $task->rubric,
                'is_published'       => $data['is_published'] ?? $task->is_published,
            ], fn ($v) => $v !== null));

            return $task->fresh(['creator']);
        });
    }

    /**
     * Delete a speaking task
     */
    public function deleteSpeakingTask(SpeakingTask $task): bool
    {
        return DB::transaction(function () use ($task) {
            // Check if there are any submissions for this task
            if ($task->submissions()->exists()) {
                throw new Exception('Cannot delete speaking task that has submissions');
            }

            // Delete assignments first
            $task->assignments()->delete();

            return $task->delete();
        });
    }

    /**
     * Duplicate a speaking task
     */
    public function duplicateSpeakingTask(SpeakingTask $task, array $overrides = []): SpeakingTask
    {
        return DB::transaction(function () use ($task, $overrides) {
            $newTask = SpeakingTask::create([
                'title'              => $overrides['title'] ?? $task->title . ' (Copy)',
                'description'        => $overrides['description'] ?? $task->description,
                'instructions'       => $task->instructions,
                'difficulty_level'   => $task->difficulty_level,
                'time_limit_seconds' => $task->time_limit_seconds,
                'topic'              => $task->topic,
                'situation_context'  => $task->situation_context,
                'questions'          => $task->questions,
                'sample_audio'       => $task->sample_audio,
                'rubric'             => $task->rubric,
                'is_published'       => false,
                'created_by'         => Auth::id(),
            ]);

            return $newTask->fresh(['creator']);
        });
    }

    /**
     * Publish a speaking task
     */
    public function publishSpeakingTask(SpeakingTask $task): SpeakingTask
    {
        $task->update(['is_published' => true]);
        return $task->fresh();
    }

    /**
     * Unpublish a speaking task
     */
    public function unpublishSpeakingTask(SpeakingTask $task): SpeakingTask
    {
        $task->update(['is_published' => false]);
        return $task->fresh();
    }

    /**
     * Assign a speaking task to classes or students
     */
    public function assignSpeakingTask(SpeakingTask $task, array $data): array
    {
        return DB::transaction(function () use ($task, $data) {
            $assignments = [];

            if ($data['assignment_type'] === 'class' && !empty($data['class_ids'])) {
                foreach ($data['class_ids'] as $classId) {
                    $assignments[] = SpeakingTaskAssignment::updateOrCreate(
                        [
                            'speaking_task_id' => $task->id,
                            'class_id'         => $classId,
                        ],
                        [
                            'assigned_by'  => Auth::id(),
                            'due_date'     => $data['due_date'] ?? null,
                            'assigned_at'  => now(),
                            'allow_retake' => $data['allow_retake'] ?? true,
                            'max_attempts' => $data['max_attempts'] ?? 3,
                        ]
                    );
                }
            }

            return [
                'task_id'     => $task->id,
                'assignments' => count($assignments),
            ];
        });
    }
}