<?php

namespace App\Services\V1\Assignment;

use App\Models\Assignment;
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
    /**
     * Assign a task or test to a class.
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
                'class_id'    => $data['class_id'],
                'test_id'     => $data['test_id'],
                'assigned_by' => Auth::id(),
                'title'       => $data['title'] ?? $source->title,
                'description' => $data['description'] ?? $source->description,
                'due_date'    => $data['due_date'],
                'is_published'=> $data['is_published'] ?? false,
                'max_attempts'=> $data['max_attempts'] ?? 3,
                'instructions'=> $data['instructions'] ?? null,
                'status'      => 'active',
                'source_type' => 'manual',
                'type'        => $source->type,
            ]);
        } else {
            $taskType = $data['task_type'];
            $source = $this->getAndVerifyTask($taskType, $data['task_id']);
            if (!$source) {
                throw new \Exception('Task not found or you do not have permission to assign it');
            }

            $assignment = Assignment::create([
                'class_id'    => $data['class_id'],
                'task_id'     => $data['task_id'],
                'task_type'   => $taskType,
                'assigned_by' => Auth::id(),
                'title'       => $data['title'] ?? $source->title,
                'description' => $data['description'] ?? ($source->description ?? null),
                'due_date'    => $data['due_date'],
                'is_published'=> $data['is_published'] ?? false,
                'max_attempts'=> $data['max_attempts'] ?? 3,
                'instructions'=> $data['instructions'] ?? null,
                'status'      => 'active',
                'source_type' => 'manual',
                'type'        => $this->normaliseType($taskType),
            ]);
        }

        $studentCount = $this->createStudentAssignments($assignment);

        return [
            'assignment'   => $assignment->load(['class', 'assignedBy']),
            'source'       => $source,
            'student_count'=> $studentCount,
        ];
    }

    /**
     * Get assignments for a class.
     */
    public function getClassAssignments(string $classId): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $isTeacher = $user->teacherClasses()->where('id', $classId)->exists();
        $isStudent = $user->studentClasses()->where('classes.id', $classId)->exists();
        $isAdmin = $user->isAdmin();

        if (!$isTeacher && !$isStudent && !$isAdmin) {
            return collect();
        }

        return Assignment::where('class_id', $classId)
            ->with(['test', 'class', 'assignedBy'])
            ->get()
            ->map(function ($assignment) use ($isStudent, $user) {
                $item = [
                    'assignment' => $assignment,
                    'type'       => $assignment->type ?? 'general',
                    'unified'    => true,
                ];

                if ($isStudent) {
                    $studentAssignment = StudentAssignment::where('assignment_id', $assignment->id)
                        ->where('student_id', $user->id)
                        ->first();

                    $item['student_progress'] = $studentAssignment ? [
                        'status'        => $studentAssignment->status,
                        'attempt_count' => $studentAssignment->attempt_count ?? 0,
                        'score'         => $studentAssignment->score,
                    ] : null;
                }

                return $item;
            })
            ->sortByDesc(fn($item) => $item['assignment']->created_at)
            ->values();
    }

    /**
     * Get assignment statistics.
     */
    public function getAssignmentStatistics(string $assignmentId, string $type): array
    {
        $assignment = $this->findAssignment($assignmentId);
        $user = Auth::user();
        if (!$assignment || (!$this->verifyAssignmentOwnership($assignment) && !$user->isAdmin())) {
            throw new \Exception('Assignment not found or access denied');
        }

        $studentAssignments = StudentAssignment::where('assignment_id', $assignmentId)
            ->with('student')
            ->get();

        return [
            'assignment'     => $assignment,
            'type'           => $assignment->type ?? $type,
            'unified'        => true,
            'statistics'     => $this->calculateStatistics($studentAssignments),
            'student_details'=> $studentAssignments,
        ];
    }

    /**
     * Update assignment.
     */
    public function updateAssignment(string $assignmentId, string $type, array $data): array
    {
        $assignment = $this->findAssignment($assignmentId);

        if (!$assignment || (!$this->verifyAssignmentOwnership($assignment) && !Auth::user()->isAdmin())) {
            throw new \Exception('Assignment not found or access denied');
        }

        $assignment->update($data);

        return [
            'assignment' => $assignment->fresh(),
            'type'       => $assignment->type ?? $type,
            'unified'    => true,
        ];
    }

    /**
     * Delete assignment.
     */
    public function deleteAssignment(string $assignmentId, string $type): void
    {
        $assignment = $this->findAssignment($assignmentId);

        if (!$assignment || (!$this->verifyAssignmentOwnership($assignment) && !Auth::user()->isAdmin())) {
            throw new \Exception('Assignment not found or access denied');
        }

        StudentAssignment::where('assignment_id', $assignmentId)->delete();
        $assignment->delete();
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    private function findAssignment(string $assignmentId): ?Assignment
    {
        return Assignment::with(['test', 'class', 'assignedBy'])->find($assignmentId);
    }

    private function verifyTeacherOwnsClass(string $classId): bool
    {
        return Auth::user()->teacherClasses()->where('id', $classId)->exists();
    }

    private function verifyAssignmentOwnership(Assignment $assignment): bool
    {
        if ($assignment->assigned_by === Auth::id()) {
            return true;
        }
        return $this->verifyTeacherOwnsClass($assignment->class_id);
    }

    private function getAndVerifyTest(string $testId): ?Test
    {
        return Test::where('id', $testId)->where('creator_id', Auth::id())->first();
    }

    private function getAndVerifyTask(string $type, string $taskId)
    {
        $model  = $this->getTaskModel($type);
        $column = $this->getCreatorColumn($type);

        return $model::where('id', $taskId)->where($column, Auth::id())->first();
    }

    private function getCreatorColumn(string $type): string
    {
        return match ($type) {
            'listening_task', 'speaking_task', 'reading_task' => 'created_by',
            default => 'creator_id',
        };
    }

    private function getTaskModel(string $type): string
    {
        return match ($type) {
            'writing_task'   => WritingTask::class,
            'reading_task'   => ReadingTask::class,
            'listening_task' => ListeningTask::class,
            'speaking_task'  => SpeakingTask::class,
            default          => throw new \Exception("Invalid task type: {$type}"),
        };
    }

    /** Strip _task suffix so the type column is always the short form. */
    private function normaliseType(string $type): string
    {
        return str_replace('_task', '', $type);
    }

    private function createStudentAssignments(Assignment $assignment): int
    {
        $studentIds = ClassEnrollment::where('class_id', $assignment->class_id)
            ->where('status', 'active')
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now  = now();
        $rows = $studentIds->map(fn($studentId) => [
            'id'              => (string) Str::uuid(),
            'assignment_id'   => $assignment->id,
            'student_id'      => $studentId,
            'assignment_type' => $assignment->type,
            'status'          => StudentAssignment::STATUS_NOT_STARTED,
            'attempt_number'  => 1,
            'attempt_count'   => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        StudentAssignment::insert($rows);

        return count($rows);
    }

    private function calculateStatistics($studentAssignments): array
    {
        $total = $studentAssignments->count();

        return [
            'total_students'  => $total,
            'completed'       => $studentAssignments->where('status', 'completed')->count(),
            'in_progress'     => $studentAssignments->where('status', 'in_progress')->count(),
            'not_started'     => $studentAssignments->where('status', 'not_started')->count(),
            'submitted'       => $studentAssignments->where('status', 'submitted')->count(),
            'overdue'         => $studentAssignments->where('status', 'overdue')->count(),
            'average_score'   => $studentAssignments->whereNotNull('score')->avg('score'),
            'completion_rate' => $total > 0
                ? round(($studentAssignments->whereIn('status', ['completed', 'submitted'])->count() / $total) * 100, 2)
                : 0,
        ];
    }
}
