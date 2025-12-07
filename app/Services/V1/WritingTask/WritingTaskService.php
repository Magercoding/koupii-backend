<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingTaskService
{
    /**
     * Create a new writing task.
     */
    public function create(array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($data, $request) {
            $task = WritingTask::create([
                'id' => Str::uuid(),
                'creator_id' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'instructions' => $data['instructions'] ?? null,
                'sample_answer' => $data['sample_answer'] ?? null,
                'word_limit' => $data['word_limit'] ?? null,
                'allow_retake' => $data['allow_retake'] ?? false,
                'max_retake_attempts' => $data['max_retake_attempts'] ?? null,
                'retake_options' => $data['retake_options'] ?? null,
                'timer_type' => $data['timer_type'] ?? 'none',
                'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
                'allow_submission_files' => $data['allow_submission_files'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'due_date' => $data['due_date'] ?? null,
            ]);

            return $task->load('creator');
        });
    }

    /**
     * Update an existing writing task.
     */
    public function updateTask(WritingTask $task, array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($task, $data, $request) {
            $task->update(array_filter([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'instructions' => $data['instructions'] ?? $task->instructions,
                'sample_answer' => $data['sample_answer'] ?? $task->sample_answer,
                'word_limit' => $data['word_limit'] ?? $task->word_limit,
                'allow_retake' => $data['allow_retake'] ?? $task->allow_retake,
                'max_retake_attempts' => $data['max_retake_attempts'] ?? $task->max_retake_attempts,
                'retake_options' => $data['retake_options'] ?? $task->retake_options,
                'timer_type' => $data['timer_type'] ?? $task->timer_type,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? $task->time_limit_seconds,
                'allow_submission_files' => $data['allow_submission_files'] ?? $task->allow_submission_files,
                'is_published' => $data['is_published'] ?? $task->is_published,
                'due_date' => $data['due_date'] ?? $task->due_date,
            ]));

            return $task->load('creator');
        });
    }

    /**
     * Duplicate a writing task.
     */
    public function duplicateTask(WritingTask $originalTask, array $overrides = []): WritingTask
    {
        return DB::transaction(function () use ($originalTask, $overrides) {
            $taskData = $originalTask->toArray();

            // Remove fields that shouldn't be duplicated
            unset($taskData['id'], $taskData['created_at'], $taskData['updated_at']);

            // Set new values
            $taskData['creator_id'] = Auth::id();
            $taskData['title'] = $overrides['title'] ?? $taskData['title'] . ' (Copy)';
            $taskData['is_published'] = false; // Always start as unpublished

            // Apply any other overrides
            $taskData = array_merge($taskData, $overrides);

            return $this->create($taskData, request());
        });
    }
}