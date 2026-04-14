<?php

namespace App\Services\V1\WritingTask;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use App\Models\Assignment;
use App\Helpers\FileUploadHelper;
use App\Traits\CreatesStudentAssignments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WritingTaskService
{
    use CreatesStudentAssignments;
    /**
     * Create a new writing task.
     *
     * Task 8.1: Persist passages JSON column; convert timer_mode/timer_settings.
     * Task 8.2: Handle image_context file uploads per passage.
     * Task 8.3: Create Assignment record when class_id is provided.
     */
    public function create(array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($data, $request) {
            // --- Task 8.1: timer conversion ---
            $timerType        = $this->mapTimerMode($data['timer_mode'] ?? null);
            $timeLimitSeconds = $this->parseTimerSettings($data['timer_settings'] ?? null);

            // --- Task 8.1: read passages ---
            $passages = $data['passages'] ?? null;

            // Persist the task first (without images) so we have a real task ID for the upload path
            $task = WritingTask::create([
                'id'                     => Str::uuid(),
                'creator_id'             => Auth::id(),
                'title'                  => $data['title'],
                'description'            => $data['description'] ?? null,
                'instructions'           => $data['instructions'] ?? null,
                'sample_answer'          => $data['sample_answer'] ?? null,
                'word_limit'             => $data['word_limit'] ?? null,
                'allow_retake'           => $data['allow_retake'] ?? false,
                'max_retake_attempts'    => $data['max_retake_attempts'] ?? null,
                'retake_options'         => $data['retake_options'] ?? null,
                'timer_type'             => $timerType,
                'time_limit_seconds'     => $timeLimitSeconds ?? ($data['time_limit_seconds'] ?? null),
                'allow_submission_files' => $data['allow_submission_files'] ?? false,
                'is_published'           => $data['is_published'] ?? false,
                'due_date'               => $data['due_date'] ?? null,
                'questions'              => $data['questions'] ?? null,
                'passages'               => $passages,
            ]);

            // --- Task 8.2: upload image_context files now that we have the real task id ---
            if ($passages && $request) {
                $passages = $this->handlePassageImageUploads($passages, $request, $task->id);
                $task->update(['passages' => $passages]);
            }

            // --- Task 8.3: create Assignment when class_id is provided ---
            if (!empty($data['class_id'])) {
                Log::info('Attempting to assign writing task to class', [
                    'class_id' => $data['class_id'],
                    'task_id'  => $task->id,
                ]);

                $classExists = DB::table('classes')->where('id', $data['class_id'])->exists();

                if ($classExists) {
                    Log::info('Class exists, creating assignment');
                    try {
                        $assignment = Assignment::create([
                            'id'           => Str::uuid(),
                            'class_id'     => $data['class_id'],
                            'task_id'      => $task->id,
                            'task_type'    => 'writing_task',
                            'assigned_by'  => Auth::id(),
                            'title'        => $task->title,
                            'due_date'     => $data['due_date'] ?? null,
                            'is_published' => $data['is_published'] ?? false,
                            'status'       => ($data['is_published'] ?? false) ? 'active' : 'inactive',
                            'source_type'  => 'manual',
                            'type'         => 'writing',
                            'max_attempts' => $data['max_repetition_count'] ?? $data['max_attempts'] ?? 3,
                        ]);
                        $this->createStudentAssignmentsForAssignment($assignment);
                        Log::info('Assignment created successfully', ['assignment_id' => $assignment->id]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create assignment', ['error' => $e->getMessage()]);
                        throw $e;
                    }
                } else {
                    Log::warning('Class does not exist', ['class_id' => $data['class_id']]);
                }
            } else {
                Log::info('No class_id provided for writing task');
            }

            return $task->load('creator');
        });
    }

    /**
     * Map timer mode string to timer_type value.
     */
    private function mapTimerMode(?string $timerMode): string
    {
        return match ($timerMode) {
            'countdown' => 'countdown',
            'countup'   => 'countup',
            default     => 'none',
        };
    }

    /**
     * Convert timer_settings (hours/minutes/seconds) to total seconds.
     * Accepts either a JSON string or an array.
     */
    private function parseTimerSettings(mixed $timerSettings): ?int
    {
        if (empty($timerSettings)) {
            return null;
        }

        if (is_string($timerSettings)) {
            $timerSettings = json_decode($timerSettings, true);
        }

        if (!is_array($timerSettings)) {
            return null;
        }

        $hours   = (int) ($timerSettings['hours']   ?? 0);
        $minutes = (int) ($timerSettings['minutes'] ?? 0);
        $seconds = (int) ($timerSettings['seconds'] ?? 0);

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * Iterate passages and upload any image_context files found in the $request.
     * Replaces the File object with the stored URL string.
     */
    private function handlePassageImageUploads(array $passages, Request $request, string $taskId): array
    {
        foreach ($passages as $i => &$passage) {
            $file = $request->file("passages.{$i}.image_context");
            if ($file && $file->isValid()) {
                $passage['image_context'] = FileUploadHelper::upload(
                    $file,
                    "writing/images/{$taskId}"
                );
            }
        }
        unset($passage);

        return $passages;
    }

    /**
     * Update an existing writing task.
     */
    public function updateTask(WritingTask $task, array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($task, $data, $request) {
            $updateData = [];
            $fields = [
                'title', 'description', 'instructions', 'sample_answer', 
                'word_limit', 'allow_retake', 'max_retake_attempts', 
                'retake_options', 'timer_type', 'time_limit_seconds', 
                'allow_submission_files', 'is_published', 'due_date', 'questions'
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $task->update($updateData);
            }

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