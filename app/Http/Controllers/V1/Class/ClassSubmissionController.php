<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\StudentAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassSubmissionController extends Controller
{
    private function normalizeStatus(?string $status): ?string
    {
        if (!$status || $status === 'all') {
            return null;
        }

        return match ($status) {
            'pending' => StudentAssignment::STATUS_NOT_STARTED,
            default => $status,
        };
    }

    private function normalizeTaskType(?string $taskType): ?array
    {
        if (!$taskType || $taskType === 'all') {
            return null;
        }

        return match ($taskType) {
            'reading', 'reading_task' => ['reading', 'reading_task'],
            'listening', 'listening_task' => ['listening', 'listening_task'],
            'speaking', 'speaking_task' => ['speaking', 'speaking_task'],
            'writing', 'writing_task' => ['writing', 'writing_task'],
            default => [$taskType],
        };
    }

    /**
     * Get all student submissions for a specific class
     */
    public function index(Request $request, string $classId): JsonResponse
    {
        try {
            $user = Auth::user();

            // Verify class exists and user is allowed to access it
            $class = Classes::where('id', $classId)->firstOrFail();

            $isAdmin = $user->role === 'admin';
            $isTeacher = $class->hasTeacher($user->id);
            $isStudent = $class->students()->where('users.id', $user->id)->exists();

            if (!($isAdmin || $isTeacher || $isStudent)) {
                return response()->json([
                    'message' => 'You are not authorized to view submissions for this class',
                ], 403);
            }

            // Fetch all student assignments linked to this class
            $query = StudentAssignment::whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })->with(['student:id,name,email,avatar', 'test:id,title,type', 'assignment']);

            // Students can only view their own submissions
            if ($isStudent && !$isAdmin && !$isTeacher) {
                $query->where('student_id', $user->id);
            }

            // Filter: Search by student name
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            }

            // Filter: Status
            $status = $this->normalizeStatus($request->input('status'));
            if ($status) {
                $query->where('status', $status);
            }

            // Filter: Task Type
            $taskTypes = $this->normalizeTaskType($request->input('task_type'));
            if ($taskTypes) {
                $query->where(function ($q) use ($taskTypes) {
                    $q->whereHas('test', function ($subQ) use ($taskTypes) {
                        $subQ->whereIn('type', $taskTypes);
                    })->orWhereHas('assignment', function ($subQ) use ($taskTypes) {
                        $subQ->whereIn('task_type', $taskTypes)->orWhereIn('type', $taskTypes);
                    });
                });
            }

            $submissions = $query->orderBy('updated_at', 'desc')
                ->paginate($request->input('per_page', 20));

            // Transform for consistent frontend integration
            $items = collect($submissions->items())->map(function ($submission) {
                $title = 'Untitled Task';
                $type = '-';

                if ($submission->test) {
                    $title = $submission->test->title;
                    $type = $submission->test->type;
                } elseif ($submission->assignment) {
                    $title = $submission->assignment->getAssignmentTitle();
                    $type = $submission->assignment->task_type ?? $submission->assignment->type ?? '-';
                }

                return [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'attempt_number' => $submission->attempt_number ?? $submission->attempt_count ?? 1,
                    'updated_at' => $submission->updated_at,
                    'student' => [
                        'id' => $submission->student->id,
                        'name' => $submission->student->name,
                        'email' => $submission->student->email,
                        'avatar_url' => $submission->student->avatar_url,
                    ],
                    'test' => [
                        'title' => $title,
                        'type' => $type,
                    ],
                ];
            });

            return response()->json([
                'message' => 'Class submissions retrieved successfully',
                'data' => $items,
                'meta' => [
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                    'per_page' => $submissions->perPage(),
                    'total' => $submissions->total(),
                ],
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve submissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
