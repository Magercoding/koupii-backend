<?php

namespace App\Services\V1\Assignment;

use App\Models\Assignment;
use App\Models\WritingTaskAssignment;
use App\Models\ReadingTaskAssignment;
use App\Models\ListeningTaskAssignment;
use App\Models\SpeakingTaskAssignment;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use App\Models\Test;
use App\Models\WritingTask;
use App\Models\ReadingTask;
use App\Models\ListeningTask;
use App\Models\SpeakingTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssignmentService
{
    private const TASK_TYPES = ['writing_task', 'reading_task', 'listening_task', 'speaking_task'];

    /**
     * Assign a task or test to a class (creates unified Assignment)
     */
    public function assignTaskToClass(array $data): array
    {
        if (!$this->verifyTeacherOwnsClass($data['class_id'])) {
            throw new \Exception('You do not have permission to assign tasks to this class');
        }

        $sourceType = $data['source_type'] ?? 'task';
        $isTest = $sourceType === 'test';

        if ($isTest) {
            $source = $this->getAndVerifyTest($data['test_id']);
            if (!$source) {
                throw new \Exception('Test not found or you do not have permission to assign it');
            }

            $assignment = Assignment::create([
                'class_id' => $data['class_id'],
                'test_id' => $data['test_id'],
                'assigned_by' => Auth::id(),
                'title' => $data['title'] ?? $source->title . ' - Assignment',
                'description' => $data['description'] ?? $source->description,
                'due_date' => $data['due_date'],
                'is_published' => true,
                'max_attempts' => $data['max_attempts'] ?? 3,
                'instructions' => $data['instructions'] ?? null,
                'status' => 'active',
                'source_type' => 'manual',
                'type' => $source->type,
            ]);
        } else {
            $source = $this->getAndVerifyTask($data['task_type'], $data['task_id']);
            if (!$source) {
                throw new \Exception('Task not found or you do not have permission to assign it');
            }

            $assignment = Assignment::create([
                'class_id' => $data['class_id'],
                'task_id' => $data['task_id'],
                'task_type' => $data['task_type'],
                'assigned_by' => Auth::id(),
                'title' => $data['title'] ?? $source->title . ' - Assignment',
                'description' => $data['description'] ?? ($source->description ?? null),
                'due_date' => $data['due_date'],
                'is_published' => true,
                'max_attempts' => $data['max_attempts'] ?? 3,
                'instructions' => $data['instructions'] ?? null,
                'status' => 'active',
                'source_type' => 'manual',
                'type' => $data['task_type'],
            ]);
        }

        $studentCount = $this->createStudentAssignments($assignment);

        return [
            'assignment' => $assignment->load(['class', 'assignedBy']),
            'source' => $source,
            'student_count' => $studentCount,
        ];
    }

    /**
     * Get assignments for a class
     */
    public function getClassAssignments(string $classId): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $isTeacher = $user->teacherClasses()->where('id', $classId)->exists();
        $isStudent = $user->studentClasses()->where('classes.id', $classId)->exists();

        if (!$isTeacher && !$isStudent) {
            return collect();
        }

        // Get unified assignments (both test-based and task-based)
        $unifiedAssignments = Assignment::where('class_id', $classId)
            ->with(['test', 'class', 'assignedBy'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'assignment' => $assignment,
                    'type' => $assignment->type ?? 'general',
                    'unified' => true,
                ];
            });

        // Get legacy task assignments for backward compatibility
        $legacyAssignments = collect();

        $legacyAssignments = $legacyAssignments
            ->merge($this->getLegacyAssignments(WritingTaskAssignment::class, $classId, 'writing_task', ['writingTask', 'class', 'assignedBy']))
            ->merge($this->getLegacyAssignments(ReadingTaskAssignment::class, $classId, 'reading_task', ['readingTask', 'class', 'assignedBy']))
            ->merge($this->getLegacyAssignments(ListeningTaskAssignment::class, $classId, 'listening_task', ['listeningTask', 'class', 'assignedBy']))
            ->merge($this->getLegacyAssignments(SpeakingTaskAssignment::class, $classId, 'speaking_task', ['speakingTask', 'class', 'assignedBy']));

        return $unifiedAssignments
            ->merge($legacyAssignments)
            ->sortByDesc(fn($item) => $item['assignment']->created_at);
    }

    /**
     * Get assignment statistics
     */
    public function getAssignmentStatistics(string $assignmentId, string $type): array
    {
        $assignment = $this->findAssignment($assignmentId, $type);

        if (!$assignment || !$this->verifyAssignmentOwnership($assignment)) {
            throw new \Exception('Assignment not found or access denied');
        }

        $isUnified = $assignment instanceof Assignment;

        $studentAssignments = StudentAssignment::where('assignment_id', $assignmentId)
            ->when(!$isUnified, fn($q) => $q->where('assignment_type', $type))
            ->with('student')
            ->get();

        return [
            'assignment' => $assignment,
            'type' => $type,
            'unified' => $isUnified,
            'statistics' => $this->calculateStatistics($studentAssignments),
            'student_details' => $studentAssignments,
        ];
    }

    /**
     * Update assignment
     */
    public function updateAssignment(string $assignmentId, string $type, array $data): array
    {
        $assignment = $this->findAssignment($assignmentId, $type);

        if (!$assignment || !$this->verifyAssignmentOwnership($assignment)) {
            throw new \Exception('Assignment not found or access denied');
        }

        $assignment->update($data);

        return [
            'assignment' => $assignment->fresh(),
            'type' => $type,
            'unified' => $assignment instanceof Assignment,
        ];
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(string $assignmentId, string $type): void
    {
        $assignment = $this->findAssignment($assignmentId, $type);

        if (!$assignment || !$this->verifyAssignmentOwnership($assignment)) {
            throw new \Exception('Assignment not found or access denied');
        }

        StudentAssignment::where('assignment_id', $assignmentId)->delete();
        $assignment->delete();
    }

    // ─── Private Helpers ─────────────────────────────────────────────

    private function verifyTeacherOwnsClass(string $classId): bool
    {
        return Auth::user()->teacherClasses()->where('id', $classId)->exists();
    }

    private function verifyAssignmentOwnership($assignment): bool
    {
        if ($assignment instanceof Assignment) {
            if ($assignment->assigned_by === Auth::id()) {
                return true;
            }
            return $this->verifyTeacherOwnsClass($assignment->class_id);
        }
        return $assignment->assigned_by === Auth::id();
    }

    private function getAndVerifyTest(string $testId): ?Test
    {
        return Test::where('id', $testId)
            ->where('creator_id', Auth::id())
            ->first();
    }

    private function getAndVerifyTask(string $type, string $taskId)
    {
        $model = $this->getTaskModel($type);
        return $model::where('id', $taskId)
            ->where('creator_id', Auth::id())
            ->first();
    }

    private function getTaskModel(string $type): string
    {
        return match ($type) {
            'writing_task' => WritingTask::class,
            'reading_task' => ReadingTask::class,
            'listening_task' => ListeningTask::class,
            'speaking_task' => SpeakingTask::class,
            default => throw new \Exception("Invalid task type: {$type}"),
        };
    }

    private function createStudentAssignments(Assignment $assignment): int
    {
        $studentIds = ClassEnrollment::where('class_id', $assignment->class_id)
            ->where('status', 'active')
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $studentIds->map(fn($studentId) => [
            'id' => (string) Str::uuid(),
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
            'assignment_type' => $assignment->type,
            'status' => StudentAssignment::STATUS_NOT_STARTED,
            'attempt_number' => 1,
            'attempt_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        StudentAssignment::insert($rows);

        return count($rows);
    }

    private function findAssignment(string $assignmentId, string $type)
    {
        // Try legacy task assignment models first
        $legacy = match ($type) {
            'writing_task' => WritingTaskAssignment::with(['writingTask', 'class', 'assignedBy'])->find($assignmentId),
            'reading_task' => ReadingTaskAssignment::with(['readingTask', 'class', 'assignedBy'])->find($assignmentId),
            'listening_task' => ListeningTaskAssignment::with(['listeningTask', 'class', 'assignedBy'])->find($assignmentId),
            'speaking_task' => SpeakingTaskAssignment::with(['speakingTask', 'class', 'assignedBy'])->find($assignmentId),
            default => null,
        };

        if ($legacy) {
            return $legacy;
        }

        // Fallback to unified Assignment model
        return Assignment::with(['test', 'class', 'assignedBy'])->find($assignmentId);
    }

    private function getLegacyAssignments(string $model, string $classId, string $type, array $with): \Illuminate\Support\Collection
    {
        return $model::where('class_id', $classId)
            ->with($with)
            ->get()
            ->map(fn($assignment) => [
                'assignment' => $assignment,
                'type' => $type,
                'unified' => false,
            ]);
    }

    private function calculateStatistics($studentAssignments): array
    {
        $total = $studentAssignments->count();

        return [
            'total_students' => $total,
            'completed' => $studentAssignments->where('status', 'completed')->count(),
            'in_progress' => $studentAssignments->where('status', 'in_progress')->count(),
            'not_started' => $studentAssignments->where('status', 'not_started')->count(),
            'submitted' => $studentAssignments->where('status', 'submitted')->count(),
            'overdue' => $studentAssignments->where('status', 'overdue')->count(),
            'average_score' => $studentAssignments->whereNotNull('score')->avg('score'),
            'completion_rate' => $total > 0
                ? round(($studentAssignments->whereIn('status', ['completed', 'submitted'])->count() / $total) * 100, 2)
                : 0,
        ];
    }
}