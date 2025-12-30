<?php

namespace App\Http\Controllers\V1\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assignment\AssignTaskRequest;
use App\Http\Requests\V1\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\V1\Assignment\AssignmentResource;
use App\Http\Resources\V1\Assignment\AssignmentStatsResource;
use App\Services\V1\Assignment\AssignmentService;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignmentService
    ) {}

    /**
     * Assign a task to a class
     */
    public function assignTask(AssignTaskRequest $request): JsonResponse
    {
        try {
            $result = $this->assignmentService->assignTaskToClass($request->validated());
            
            return response()->json([
                'message' => 'Task assigned successfully',
                'data' => [
                    'assignment_id' => $result['assignment']->id,
                    'assigned_to_students' => $result['student_count'],
                    'task_title' => $result['task']->title,
                    'class_name' => $result['assignment']->class->name,
                    'due_date' => $result['assignment']->due_date
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Task not found or you do not have permission to assign it' ? 404 : 403);
        }
    }

    /**
     * Get assignments for a class (teacher view)
     */
    public function getClassAssignments(string $classId): JsonResponse
    {
        try {
            $assignments = $this->assignmentService->getClassAssignments($classId);
            
            return response()->json([
                'message' => 'Assignments retrieved successfully',
                'data' => AssignmentResource::collection($assignments)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
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
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
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
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(string $assignmentId, string $type): JsonResponse
    {
        try {
            $this->assignmentService->deleteAssignment($assignmentId, $type);
            
            return response()->json([
                'message' => 'Assignment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}