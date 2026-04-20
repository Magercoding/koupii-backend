<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\SpeakingTask;
use App\Models\Assignment;
use App\Helpers\FileUploadHelper;
use App\Traits\CreatesStudentAssignments;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class SpeakingTaskService
{
    use CreatesStudentAssignments;
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
                ->whereHas('assignments', function ($q) use ($user) {
                    $q->whereHas('class.enrollments', function ($e) use ($user) {
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
            ->when($filters['class_id'] ?? null, function ($q, $classId) {
                $q->where('class_id', $classId);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new speaking task
     *
     * Task 5.1: Accept `passages` key and persist to `speaking_tasks.questions` JSON column.
     *           Convert timer_mode/timer_settings → timer_type/time_limit_seconds.
     * Task 5.2: Handle `image_context` file uploads per passage.
     * Task 5.3: Create Assignment record when class_id is provided.
     */
    public function createSpeakingTask(array $data, ?Request $request = null): SpeakingTask
    {
        return DB::transaction(function () use ($data, $request) {
            // --- Task 5.1: timer conversion ---
            $timerType = $this->mapTimerMode($data['timer_mode'] ?? null);
            $timeLimitSeconds = $this->parseTimerSettings($data['timer_settings'] ?? null);

            // --- Task 5.1: read passages (not sections) ---
            $passages = $data['passages'] ?? $data['sections'] ?? $data['questions'] ?? null;

            // Persist the task first (without images) so we have a real task ID for the upload path
            $task = SpeakingTask::create([
                'class_id'           => $data['class_id'] ?? null,
                'title'              => $data['title'],
                'description'        => $data['description'] ?? null,
                'instructions'       => $data['instructions'] ?? null,
                'difficulty_level'   => $data['difficulty'] ?? $data['difficulty_level'] ?? 'beginner',
                'timer_type'         => $timerType,
                'time_limit_seconds' => $timeLimitSeconds ?? ($data['time_limit_seconds'] ?? null),
                'topic'              => $data['topic'] ?? null,
                'situation_context'  => $data['situation_context'] ?? null,
                'questions'          => $passages,
                'sample_audio'       => $data['sample_audio'] ?? null,
                'rubric'             => $data['rubric'] ?? null,
                'is_published'       => $data['is_published'] ?? false,
                'created_by'         => Auth::id(),
            ]);

            // --- Task 5.2: upload image_context files now that we have the real task id ---
            if ($passages && $request) {
                $passages = $this->handlePassageImageUploads($passages, $request, $task->id);
                $task->update(['questions' => $passages]);
            }

            // --- Task 5.3: create Assignment only when explicitly requested ---
            if (!empty($data['class_id']) && !empty($data['assign_on_create'])) {
                Log::info('Attempting to assign speaking task to class', [
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
                            'task_type'    => 'speaking_task',
                            'assigned_by'  => Auth::id(),
                            'title'        => $task->title,
                            'due_date'     => $data['due_date'] ?? null,
                            'is_published' => $data['is_published'] ?? false,
                            'status'       => ($data['is_published'] ?? false) ? 'active' : 'inactive',
                            'source_type'  => 'manual',
                            'type'         => 'speaking',
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
            }

            return $task->fresh(['creator']);
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
     * Replaces the File object (or leaves existing string paths) with the stored URL.
     *
     * @param array   $passages  The passages array from validated data
     * @param Request $request   The original HTTP request (contains uploaded files)
     * @param string  $taskId    Used as the storage sub-folder
     * @return array             Passages with image_context replaced by stored path strings
     */
    private function handlePassageImageUploads(array $passages, Request $request, string $taskId): array
    {
        foreach ($passages as $i => &$passage) {
            $file = $request->file("passages.{$i}.image_context");
            if ($file && $file->isValid()) {
                $passage['image_context'] = FileUploadHelper::upload(
                    $file,
                    "speaking/images/{$taskId}"
                );
            }
        }
        unset($passage);

        return $passages;
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
                'due_date'           => array_key_exists('due_date', $data) ? $data['due_date'] : $task->due_date,
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
                    $assignments[] = Assignment::updateOrCreate(
                        [
                            'task_id'   => $task->id,
                            'task_type' => 'speaking_task',
                            'class_id'  => $classId,
                        ],
                        [
                            'assigned_by' => Auth::id(),
                            'due_date'    => $data['due_date'] ?? null,
                            'max_attempts'=> $data['max_attempts'] ?? 3,
                            'title'       => $task->title,
                            'is_published'=> true,
                            'status'      => 'active',
                            'source_type' => 'manual',
                            'type'        => 'speaking',
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