<?php

namespace App\Services\V1\Assignment;

use App\Models\WritingTaskAssignment;
use App\Models\ReadingTaskAssignment;
use App\Models\ListeningTaskAssignment;
use App\Models\SpeakingTaskAssignment;
use App\Models\ClassEnrollment;
use App\Models\StudentAssignment;
use App\Models\WritingTask;
use App\Models\ReadingTask;
use App\Models\ListeningTask;
use App\Models\SpeakingTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class AssignmentService
{
    /**
     * Assign a task to a class
     */
    public function assignTaskToClass(array $data): array
    {
        // Verify teacher owns the class
        if (!$this->verifyTeacherOwnsClass($data['class_id'])) {
            throw new \Exception('You do not have permission to assign tasks to this class');
        }

        // Verify task exists and belongs to teacher
        $task = $this->getAndVerifyTask($data['task_type'], $data['task_id']);
        if (!$task) {
            throw new \Exception('Task not found or you do not have permission to assign it');
        }

        // Create assignment
        $assignment = $this->createAssignmentByType($data['task_type'], [
            'task_id' => $data['task_id'],
            'class_id' => $data['class_id'],
            'assigned_by' => Auth::id(),
            'due_date' => $data['due_date'],
            'max_attempts' => $data['max_attempts'] ?? 3,
            'instructions' => $data['instructions'] ?? null,
            'auto_grade' => $data['auto_grade'] ?? true,
            'status' => 'active'
        ]);

        // Create student assignments
        $studentCount = $this->createStudentAssignments($assignment, $data['task_type'], $data['class_id']);

        return [
            'assignment' => $assignment,
            'task' => $task,
            'student_count' => $studentCount
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

        $assignments = collect();

        // Get new unified assignments (from automatic assignment system)
        $unifiedAssignments = \App\Models\Assignment::where('class_id', $classId)
            ->with(['test', 'class'])
            ->get()
            ->map(function($assignment) {
                return [
                    'assignment' => $assignment,
                    'type' => $assignment->type,
                    'unified' => true
                ];
            });

        // Get legacy assignment types for backward compatibility
        $writingAssignments = WritingTaskAssignment::where('class_id', $classId)
            ->with(['writingTask', 'class', 'assignedBy'])
            ->get()
            ->map(fn($assignment) => ['assignment' => $assignment, 'type' => 'writing_task', 'unified' => false]);

        $readingAssignments = ReadingTaskAssignment::where('class_id', $classId)
            ->with(['readingTask', 'class', 'assignedBy'])
            ->get()
            ->map(fn($assignment) => ['assignment' => $assignment, 'type' => 'reading_task', 'unified' => false]);

        $listeningAssignments = ListeningTaskAssignment::where('class_id', $classId)
            ->with(['listeningTask', 'class', 'assignedBy'])
            ->get()
            ->map(fn($assignment) => ['assignment' => $assignment, 'type' => 'listening_task', 'unified' => false]);

        $speakingAssignments = SpeakingTaskAssignment::where('class_id', $classId)
            ->with(['speakingTask', 'class', 'assignedBy'])
            ->get()
            ->map(fn($assignment) => ['assignment' => $assignment, 'type' => 'speaking_task', 'unified' => false]);

        return $assignments->merge($unifiedAssignments)
            ->merge($writingAssignments)
            ->merge($readingAssignments)
            ->merge($listeningAssignments)
            ->merge($speakingAssignments)
            ->sortByDesc(function($item) {
                return $item['assignment']->created_at;
            });
    }

    /**
     * Get assignment statistics
     */
    public function getAssignmentStatistics(string $assignmentId, string $type): array
    {
        $assignment = $this->getAssignmentByType($type, $assignmentId);

        if (!$assignment || $assignment->assigned_by !== Auth::id()) {
            throw new \Exception('Assignment not found or access denied');
        }

        $studentAssignments = StudentAssignment::where([
            'assignment_id' => $assignmentId,
            'assignment_type' => $type
        ])->with('student')->get();

        return [
            'assignment' => $assignment,
            'type' => $type,
            'statistics' => $this->calculateStatistics($studentAssignments),
            'student_details' => $studentAssignments
        ];
    }

    /**
     * Update assignment
     */
    public function updateAssignment(string $assignmentId, string $type, array $data): mixed
    {
        $assignment = $this->getAssignmentByType($type, $assignmentId);

        if (!$assignment || $assignment->assigned_by !== Auth::id()) {
            throw new \Exception('Assignment not found or access denied');
        }

        $assignment->update($data);
        return ['assignment' => $assignment->fresh(), 'type' => $type];
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(string $assignmentId, string $type): void
    {
        $assignment = $this->getAssignmentByType($type, $assignmentId);

        if (!$assignment || $assignment->assigned_by !== Auth::id()) {
            throw new \Exception('Assignment not found or access denied');
        }

        // Delete related student assignments
        StudentAssignment::where([
            'assignment_id' => $assignmentId,
            'assignment_type' => $type
        ])->delete();

        $assignment->delete();
    }

    /**
     * Private helper methods
     */
    private function verifyTeacherOwnsClass(string $classId): bool
    {
        return Auth::user()->teacherClasses()
            ->where('id', $classId)
            ->exists();
    }

    private function getAndVerifyTask(string $type, string $taskId)
    {
        $taskModel = $this->getTaskModel($type);
        return $taskModel::where([
            'id' => $taskId,
            'creator_id' => Auth::id()
        ])->first();
    }

    private function getTaskModel(string $type): string
    {
        return match ($type) {
            'writing_task' => WritingTask::class,
            'reading_task' => ReadingTask::class,
            'listening_task' => ListeningTask::class,
            'speaking_task' => SpeakingTask::class
        };
    }

    private function createAssignmentByType(string $type, array $data)
    {
        $fieldMapping = [
            'writing_task' => ['writing_task_id' => 'task_id'],
            'reading_task' => ['reading_task_id' => 'task_id'],
            'listening_task' => ['listening_task_id' => 'task_id'],
            'speaking_task' => ['speaking_task_id' => 'task_id']
        ];

        if (isset($fieldMapping[$type])) {
            $specificField = array_key_first($fieldMapping[$type]);
            $data[$specificField] = $data['task_id'];
            unset($data['task_id']);
        }

        return match ($type) {
            'writing_task' => WritingTaskAssignment::create($data),
            'reading_task' => ReadingTaskAssignment::create($data),
            'listening_task' => ListeningTaskAssignment::create($data),
            'speaking_task' => SpeakingTaskAssignment::create($data)
        };
    }

    private function createStudentAssignments($assignment, string $type, string $classId): int
    {
        $students = ClassEnrollment::where([
            'class_id' => $classId,
            'status' => 'active'
        ])->pluck('student_id');

        foreach ($students as $studentId) {
            StudentAssignment::create([
                'student_id' => $studentId,
                'assignment_id' => $assignment->id,
                'assignment_type' => $type,
                'status' => 'pending',
                'attempt_count' => 0
            ]);
        }

        return $students->count();
    }

    private function getAssignmentByType(string $type, string $assignmentId)
    {
        return match ($type) {
            'writing_task' => WritingTaskAssignment::with(['writingTask', 'class', 'assignedBy'])->find($assignmentId),
            'reading_task' => ReadingTaskAssignment::with(['readingTask', 'class', 'assignedBy'])->find($assignmentId),
            'listening_task' => ListeningTaskAssignment::with(['listeningTask', 'class', 'assignedBy'])->find($assignmentId),
            'speaking_task' => SpeakingTaskAssignment::with(['speakingTask', 'class', 'assignedBy'])->find($assignmentId)
        };
    }

    private function calculateStatistics($studentAssignments): array
    {
        $totalStudents = $studentAssignments->count();

        return [
            'total_students' => $totalStudents,
            'completed' => $studentAssignments->where('status', 'completed')->count(),
            'in_progress' => $studentAssignments->where('status', 'in_progress')->count(),
            'pending' => $studentAssignments->where('status', 'pending')->count(),
            'submitted' => $studentAssignments->where('status', 'submitted')->count(),
            'overdue' => $studentAssignments->where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'submitted'])->count(),
            'average_score' => $studentAssignments->whereNotNull('score')->avg('score'),
            'completion_rate' => $totalStudents > 0
                ? round(($studentAssignments->whereIn('status', ['completed', 'submitted'])->count() / $totalStudents) * 100, 2)
                : 0
        ];
    }
}