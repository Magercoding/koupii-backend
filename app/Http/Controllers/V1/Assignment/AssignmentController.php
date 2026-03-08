<?php

namespace App\Http\Controllers\V1\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assignment\AssignTaskRequest;
use App\Http\Requests\V1\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\V1\Assignment\AssignmentResource;
use App\Http\Resources\V1\Assignment\AssignmentStatsResource;
use App\Services\V1\Assignment\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    /**
     * Assign a test or task to a class
     *
     * @response 201 {
     *   "message": "Assignment created successfully",
     *   "data": {
     *     "assignment_id": "uuid",
     *     "assigned_to_students": 30,
     *     "title": "Listening Practice 1 - Assignment",
     *     "class_name": "English A",
     *     "due_date": "2026-03-15"
     *   }
     * }
     */
    public function assignTask(AssignTaskRequest $request): JsonResponse
    {
        try {
            $result = $this->assignmentService->assignTaskToClass($request->validated());

            return response()->json([
                'message' => 'Assignment created successfully',
                'data' => [
                    'assignment_id' => $result['assignment']->id,
                    'assigned_to_students' => $result['student_count'],
                    'title' => $result['assignment']->title,
                    'class_name' => $result['assignment']->class->name,
                    'due_date' => $result['assignment']->due_date,
                ]
            ], 201);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 403;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * Get assignments for a class (teacher view)
     */
    public function getClassAssignments(string $classId): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getClassAssignments($classId);

            if ($assignments->isEmpty()) {
                return response()->json([
                    'message' => 'No assignments yet',
                    'data' => []
                ]);
            }

            return response()->json([
                'message' => 'Assignments retrieved successfully',
                'data' => AssignmentResource::collection($assignments)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching assignments', [
                'class_id' => $classId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Get assignment statistics
     */
    public function getAssignmentStats(string $assignmentId, string $type): JsonResponse
    {
        try {
            $stats = $this->assignmentService->getAssignmentStatistics($assignmentId, $type);

            return response()->json([
                'message' => 'Assignment statistics retrieved successfully',
                'data' => new AssignmentStatsResource($stats)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Update assignment
     */
    public function updateAssignment(UpdateAssignmentRequest $request, string $assignmentId, string $type): JsonResponse
    {
        try {
            $result = $this->assignmentService->updateAssignment(
                $assignmentId,
                $type,
                $request->validated()
            );

            return response()->json([
                'message' => 'Assignment updated successfully',
                'data' => new AssignmentResource($result)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(string $assignmentId, string $type): JsonResponse
    {
        try {
            $this->assignmentService->deleteAssignment($assignmentId, $type);

            return response()->json(['message' => 'Assignment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}