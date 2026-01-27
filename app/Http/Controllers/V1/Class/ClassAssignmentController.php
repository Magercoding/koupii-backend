<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Test;
use App\Events\TestAssignedToClass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClassAssignmentController extends Controller
{
    /**
     * Manually assign an existing test to a class
     * This is useful for tests that were created before the automatic assignment system
     */
    public function assignTestToClass(Request $request, string $classId, string $testId): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:now',
        ]);

        try {
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', Auth::id())
                ->firstOrFail();

            // Verify test ownership and that it belongs to this class
            $test = Test::where('id', $testId)
                ->where('creator_id', Auth::id())
                ->where('class_id', $classId)
                ->where('is_published', true)
                ->firstOrFail();

            // Dispatch event to create automatic assignments
            TestAssignedToClass::dispatch($test, $class, [
                'title' => $request->input('title', $test->title . ' - Assignment'),
                'description' => $request->input('description', 'Complete this test by the due date'),
                'due_date' => $request->input('due_date', now()->addDays(7)),
                'is_published' => true
            ]);

            // Get student count for response
            $studentCount = $class->enrollments()
                ->where('status', 'active')
                ->count();

            return response()->json([
                'message' => 'Test assigned to all students in ' . $class->name,
                'data' => [
                    'test_id' => $test->id,
                    'test_title' => $test->title,
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'assigned_to_students' => $studentCount,
                    'due_date' => $request->input('due_date', now()->addDays(7))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign test to class',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}