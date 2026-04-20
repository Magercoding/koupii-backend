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
    /**
     * Get all student submissions for a specific class
     */
    public function index(Request $request, string $classId): JsonResponse
    {
        try {
            $user = Auth::user();

            // Verify class exists and user has access (Teacher of the class)
            $class = Classes::where('id', $classId)
                ->where('teacher_id', $user->id)
                ->firstOrFail();

            // Fetch all student assignments linked to this class
            $query = StudentAssignment::whereHas('assignment', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })->with(['student:id,name,email,avatar', 'test:id,title,type', 'assignment']);

            // Filter: Search by student name
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            }

            // Filter: Status
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }

            // Filter: Task Type
            if ($request->filled('task_type') && $request->input('task_type') !== 'all') {
                $taskType = $request->input('task_type');
                $query->where(function ($q) use ($taskType) {
                    $q->whereHas('test', function ($subQ) use ($taskType) {
                        $subQ->where('type', $taskType);
                    })->orWhereHas('assignment', function ($subQ) use ($taskType) {
                        $subQ->where('task_type', $taskType)->orWhere('type', $taskType);
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
            ], 404);
        }
    }
}
