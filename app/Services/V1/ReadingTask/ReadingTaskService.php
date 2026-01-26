<?php

namespace App\Services\V1\ReadingTask;

use App\Models\ReadingTask;
use App\Models\Test;
use App\Helpers\FileUploadHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReadingTaskService
{
    /**
     * Get reading tasks with filters and pagination
     */
    public function getReadingTasks(array $filters = []): LengthAwarePaginator
    {
        $query = ReadingTask::with(['creator', 'assignments.classroom']);

        // Apply filters
        if (!empty($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }

        if (!empty($filters['task_type'])) {
            $query->where('task_type', $filters['task_type']);
        }

        if (!empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (!empty($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'LIKE', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new reading task
     */
    public function create(array $taskData): ReadingTask
    {
        return DB::transaction(function () use ($taskData) {
            // Handle file uploads
            $fileData = $this->handleFileUploads($taskData);

            // Prepare task data
            $readingTaskData = [
                'id' => Str::uuid(),
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'instructions' => $taskData['instructions'] ?? null,
                'task_type' => $taskData['type'] ?? 'reading',
                'difficulty' => $taskData['difficulty'],
                'timer_type' => $this->mapTimerMode($taskData['timer_mode'] ?? 'none'),
                'time_limit_seconds' => $this->parseTimerSettings($taskData['timer_settings'] ?? null),
                'allow_retake' => $taskData['allow_repetition'] ?? false,
                'max_retake_attempts' => $taskData['max_repetition_count'] ?? 0,
                'allow_submission_files' => false,
                'is_published' => $taskData['is_published'] ?? false,
                'created_by' => $taskData['created_by'] ?? Auth::id(),
                'passages' => $this->parsePassages($taskData['passages']),
                'passage_images' => $fileData['passage_images'] ?? null,
                'suggest_time_minutes' => $this->calculateSuggestedTime($taskData['passages']),
                'difficulty_level' => $taskData['difficulty'],
                'question_types' => $this->extractQuestionTypes($taskData['passages']),
            ];

            $task = ReadingTask::create($readingTaskData);

            // If class_id is provided, assign the task to the class
            if (!empty($taskData['class_id'])) {
                \Illuminate\Support\Facades\Log::info('Attempting to assign reading task to class', ['class_id' => $taskData['class_id'], 'task_id' => $task->id]);
                // Ensure class_id exists (validation should have covered this, but safe check)
                $classExists = DB::table('classes')->where('id', $taskData['class_id'])->exists();

                if ($classExists) {
                    \Illuminate\Support\Facades\Log::info('Class exists, creating assignment');
                    try {
                        $assignment = \App\Models\ReadingTaskAssignment::create([
                            'id' => Str::uuid(),
                            'reading_task_id' => $task->id,
                            'class_id' => $taskData['class_id'], // Direct assignment
                            'classroom_id' => $taskData['class_id'], // Legacy support / Constraint safety
                            'assigned_by' => Auth::id(),
                            'assigned_at' => now(),
                            'status' => $taskData['is_published'] ? 'active' : 'inactive',
                            'due_date' => null, // Default to null or allow passing it
                            'max_attempts' => $taskData['max_repetition_count'] ?? 0,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Assignment created successfully', ['assignment_id' => $assignment->id]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to create assignment', ['error' => $e->getMessage()]);
                        throw $e; // Re-throw to fail transaction
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('Class does not exist', ['class_id' => $taskData['class_id']]);
                }
            } else {
                \Illuminate\Support\Facades\Log::info('No class_id provided for reading task');
            }

            return $task->load(['creator', 'assignments.classroom']);
        });
    }

    /**
     * Update a reading task
     */
    public function update(ReadingTask $task, array $taskData): ReadingTask
    {
        return DB::transaction(function () use ($task, $taskData) {
            // Handle file uploads
            $fileData = $this->handleFileUploads($taskData);

            // Prepare update data
            $updateData = [];

            if (isset($taskData['title'])) {
                $updateData['title'] = $taskData['title'];
            }

            if (isset($taskData['description'])) {
                $updateData['description'] = $taskData['description'];
            }

            if (isset($taskData['instructions'])) {
                $updateData['instructions'] = $taskData['instructions'];
            }

            if (isset($taskData['type'])) {
                $updateData['task_type'] = $taskData['type'];
            }

            if (isset($taskData['difficulty'])) {
                $updateData['difficulty'] = $taskData['difficulty'];
                $updateData['difficulty_level'] = $taskData['difficulty'];
            }

            if (isset($taskData['timer_mode'])) {
                $updateData['timer_type'] = $this->mapTimerMode($taskData['timer_mode']);
            }

            if (isset($taskData['timer_settings'])) {
                $updateData['time_limit_seconds'] = $this->parseTimerSettings($taskData['timer_settings']);
            }

            if (isset($taskData['allow_repetition'])) {
                $updateData['allow_retake'] = $taskData['allow_repetition'];
            }

            if (isset($taskData['max_repetition_count'])) {
                $updateData['max_retake_attempts'] = $taskData['max_repetition_count'];
            }

            if (isset($taskData['is_published'])) {
                $updateData['is_published'] = $taskData['is_published'];
            }

            if (isset($taskData['passages'])) {
                $updateData['passages'] = $this->parsePassages($taskData['passages']);
                $updateData['suggest_time_minutes'] = $this->calculateSuggestedTime($taskData['passages']);
                $updateData['question_types'] = $this->extractQuestionTypes($taskData['passages']);
            }

            if (!empty($fileData['passage_images'])) {
                $updateData['passage_images'] = array_merge($task->passage_images ?? [], $fileData['passage_images']);
            }

            $task->update($updateData);

            return $task->load(['creator', 'assignments.classroom']);
        });
    }

    /**
     * Handle file uploads
     */
    private function handleFileUploads(array $data): array
    {
        $result = [];

        if (isset($data['passage_images']) && is_array($data['passage_images'])) {
            $result['passage_images'] = [];

            foreach ($data['passage_images'] as $file) {
                if ($file->isValid()) {
                    $uploadedFile = FileUploadHelper::upload($file, 'reading-passages/images');
                    if ($uploadedFile) {
                        $result['passage_images'][] = $uploadedFile;
                    }
                }
            }
        }

        if (isset($data['reference_materials']) && is_array($data['reference_materials'])) {
            $result['reference_materials'] = [];

            foreach ($data['reference_materials'] as $file) {
                if ($file->isValid()) {
                    $uploadedFile = FileUploadHelper::upload($file, 'reading-passages/materials');
                    if ($uploadedFile) {
                        $result['reference_materials'][] = $uploadedFile;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Parse passages JSON string
     */
    private function parsePassages(string $passagesJson): array
    {
        try {
            $passages = json_decode($passagesJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON in passages data');
            }
            return $passages;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to parse passages data: ' . $e->getMessage());
        }
    }

    /**
     * Map timer mode to timer type
     */
    private function mapTimerMode(?string $timerMode): string
    {
        return match ($timerMode) {
            'countdown' => 'countdown',
            'countup' => 'countup',
            default => 'none'
        };
    }

    /**
     * Parse timer settings JSON
     */
    private function parseTimerSettings(?string $timerSettings): ?int
    {
        if (empty($timerSettings)) {
            return null;
        }

        try {
            $settings = json_decode($timerSettings, true);
            if (!is_array($settings)) {
                return null;
            }

            $hours = $settings['hours'] ?? 0;
            $minutes = $settings['minutes'] ?? 0;
            $seconds = $settings['seconds'] ?? 0;

            return ($hours * 3600) + ($minutes * 60) + $seconds;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate suggested time based on passages
     */
    private function calculateSuggestedTime(string $passagesJson): int
    {
        try {
            $passages = json_decode($passagesJson, true);
            $totalQuestions = 0;

            foreach ($passages as $passage) {
                if (isset($passage['question_groups']) && is_array($passage['question_groups'])) {
                    foreach ($passage['question_groups'] as $group) {
                        if (isset($group['questions']) && is_array($group['questions'])) {
                            $totalQuestions += count($group['questions']);
                        }
                    }
                }
            }

            // 2 minutes per question as base estimate
            return max(1, $totalQuestions * 2);
        } catch (\Exception $e) {
            return 20; // Default 20 minutes
        }
    }

    /**
     * Extract question types from passages
     */
    private function extractQuestionTypes(string $passagesJson): array
    {
        try {
            $passages = json_decode($passagesJson, true);
            $questionTypes = [];

            foreach ($passages as $passage) {
                if (isset($passage['question_groups']) && is_array($passage['question_groups'])) {
                    foreach ($passage['question_groups'] as $group) {
                        if (isset($group['questions']) && is_array($group['questions'])) {
                            foreach ($group['questions'] as $question) {
                                if (isset($question['question_type'])) {
                                    $questionTypes[] = $question['question_type'];
                                }
                            }
                        }
                    }
                }
            }

            return array_unique($questionTypes);
        } catch (\Exception $e) {
            return [];
        }
    }
}