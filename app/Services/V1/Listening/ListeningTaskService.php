<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\Test;
use App\Helpers\Listening\ListeningTestHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
     * Create a new listening task (controller interface)
     */
    public function create(array $taskData, $request = null): ListeningTask
    {
        // Add created_by from authenticated user (listening_tasks uses created_by column)
        $taskData['created_by'] = Auth::id();
        $taskData['is_published'] = $taskData['is_published'] ?? false;
        
        return $this->createListeningTask($taskData);
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
     * Update a listening task (controller interface)
     * Handles nested passages structure from the frontend form.
     */
    public function updateTask(ListeningTask $task, array $taskData, $request = null): ListeningTask
    {
        return DB::transaction(function () use ($task, $taskData, $request) {
            // 1. Update basic task fields
            $updateFields = array_filter([
                'title' => $taskData['title'] ?? null,
                'description' => $taskData['description'] ?? null,
                'difficulty_level' => $taskData['difficulty'] ?? $taskData['difficulty_level'] ?? null,
                'timer_type' => $taskData['timer_mode'] ?? null,
                'is_published' => isset($taskData['is_published']) ? (bool) $taskData['is_published'] : null,
            ], fn ($v) => $v !== null);

            // Handle timer settings -> time_limit_seconds conversion
            if (!empty($taskData['timer_settings'])) {
                $hours = (int) ($taskData['timer_settings']['hours'] ?? 0);
                $minutes = (int) ($taskData['timer_settings']['minutes'] ?? 0);
                $seconds = (int) ($taskData['timer_settings']['seconds'] ?? 0);
                $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                if ($totalSeconds > 0) {
                    $updateFields['time_limit_seconds'] = $totalSeconds;
                }
            }

            // Handle allow_repetition -> allow_retake
            if (isset($taskData['allow_repetition'])) {
                $updateFields['allow_retake'] = in_array($taskData['allow_repetition'], ['on', true, 1, '1'], true);
            }
            if (isset($taskData['max_repetition_count'])) {
                $updateFields['max_retake_attempts'] = (int) $taskData['max_repetition_count'];
            }

            if (!empty($updateFields)) {
                $task->update($updateFields);
            }

            // 2. Handle passages (nested data with questions)
            if (!empty($taskData['passages']) && is_array($taskData['passages'])) {
                // Delete existing questions and recreate (atomic replace)
                $task->questions()->delete();

                $questionOrder = 0;
                foreach ($taskData['passages'] as $pIndex => $passage) {
                    // Handle audio file upload
                    if ($request && $request->hasFile("passages.{$pIndex}.audio_file")) {
                        $audioFile = $request->file("passages.{$pIndex}.audio_file");
                        $audioPath = $audioFile->store("listening/audio/{$task->id}", 'public');
                        $task->update(['audio_url' => $audioPath]);
                    }

                    // Handle question groups
                    if (!empty($passage['question_groups']) && is_array($passage['question_groups'])) {
                        foreach ($passage['question_groups'] as $gIndex => $group) {
                            // Store transcript and instruction as task-level metadata
                            if (!empty($group['transcript'])) {
                                $task->update(['transcript' => json_encode($group['transcript'])]);
                            }

                            // Handle image uploads
                            if ($request && $request->hasFile("passages.{$pIndex}.question_groups.{$gIndex}.image.file")) {
                                $imageFile = $request->file("passages.{$pIndex}.question_groups.{$gIndex}.image.file");
                                $imagePath = $imageFile->store("listening/images/{$task->id}", 'public');
                                // Could store in audio_segments or separate field depending on schema
                            }

                            // Create questions
                            if (!empty($group['questions']) && is_array($group['questions'])) {
                                foreach ($group['questions'] as $qIndex => $question) {
                                    $questionOrder++;

                                    \App\Models\ListeningQuestion::create([
                                        'listening_task_id' => $task->id,
                                        'question_type' => $question['question_type'] ?? 'multiple_choice',
                                        'question_text' => $question['question_text'] ?? '',
                                        'options' => $question['options'] ?? null,
                                        'correct_answers' => $this->normalizeCorrectAnswer($question['correct_answer'] ?? null),
                                        'points' => (int) ($question['points'] ?? $question['points_value'] ?? 1),
                                        'order_index' => $question['question_number'] ?? $questionOrder,
                                        'explanation' => $question['breakdown']['explanation'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            return $task->fresh(['creator', 'questions']);
        });
    }

    /**
     * Normalize correct_answer from frontend format to array format
     */
    private function normalizeCorrectAnswer($answer): ?array
    {
        if ($answer === null) {
            return null;
        }

        // Already an array of answer objects [{option_key, option_text}]
        if (is_array($answer) && isset($answer[0])) {
            return $answer;
        }

        // Single answer object {option_key, option_text}
        if (is_array($answer) && isset($answer['option_key'])) {
            return [$answer['option_key']];
        }

        // String answer
        if (is_string($answer)) {
            return [$answer];
        }

        return null;
    }

    /**
     * Delete a listening task
     */
    public function deleteListeningTask(ListeningTask $task): bool
    {
        return DB::transaction(function () use ($task) {
            // Delete associated audio segments
            $task->audioSegments()->delete();
            
            // Delete associated questions for this test
            if ($task->test_id) {
                $test = Test::find($task->test_id);
                if ($test) {
                    $test->questions()->delete();
                }
            }
            
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