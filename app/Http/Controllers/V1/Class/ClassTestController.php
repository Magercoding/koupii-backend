<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Test\StoreTestRequest;
use App\Http\Requests\V1\Test\UpdateTestRequest;
use App\Http\Resources\V1\Test\TestResource;
use App\Models\StudentAssignment;
use App\Models\Test;
use App\Models\Classes;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassTestController extends Controller
{
    /**
     * Get all tests for a specific class
     */
    public function index(Request $request, string $classId): JsonResponse
    {
        try {
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $tests = Test::where('class_id', $classId)
                ->with(['passages.questionGroups.questions', 'creator'])
                ->when($request->get('type'), function ($query, $type) {
                    return $query->where('type', $type);
                })
                ->when($request->get('is_published'), function ($query, $published) {
                    return $query->where('is_published', $published);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'message' => 'Class tests retrieved successfully',
                'data' => TestResource::collection($tests),
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'class_code' => $class->class_code,
                    'student_count' => $class->enrollments()->where('status', 'active')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve tests',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new test for a specific class
     */
    public function store(StoreTestRequest $request, string $classId): JsonResponse
    {
        try {
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            DB::beginTransaction();

            $testData = $request->validated();
            $testData['class_id'] = $classId;
            $testData['creator_id'] = auth()->id();

            // Create the test
            $test = Test::create($testData);

            // Create passages and questions if provided
            if (isset($testData['passages']) && is_array($testData['passages'])) {
                foreach ($testData['passages'] as $passageData) {
                    $this->createPassageWithQuestions($test, $passageData);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Test created successfully for class: ' . $class->name,
                'data' => new TestResource($test->load(['passages.questionGroups.questions', 'creator', 'class'])),
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'class_code' => $class->class_code
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create test',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a specific test within a class
     */
    public function show(string $classId, string $testId): JsonResponse
    {
        try {
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $test = Test::where('id', $testId)
                ->where('class_id', $classId)
                ->with(['passages.questionGroups.questions.options', 'creator', 'class'])
                ->firstOrFail();

            // Get test statistics
            $assignments = Assignment::where('class_id', $classId)
                ->where('test_id', $testId)
                ->with('studentAssignments')
                ->get();

            $stats = [
                'assigned_students' => $assignments->flatMap(function($assignment) {
                    return $assignment->studentAssignments;
                })->count(),
                'completed' => $assignments->flatMap(function($assignment) {
                    return $assignment->studentAssignments->where('status', 'completed');
                })->count(),
                'in_progress' => $assignments->flatMap(function($assignment) {
                    return $assignment->studentAssignments->where('status', 'in_progress');
                })->count(),
                'not_started' => $assignments->flatMap(function($assignment) {
                    return $assignment->studentAssignments->where('status', 'not_started');
                })->count(),
            ];

            return response()->json([
                'message' => 'Test details retrieved successfully',
                'data' => new TestResource($test),
                'statistics' => $stats,
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'class_code' => $class->class_code
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Test not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a test within a class
     */
    public function update(UpdateTestRequest $request, string $classId, string $testId): JsonResponse
    {
        try {
 
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $test = Test::where('id', $testId)
                ->where('class_id', $classId)
                ->firstOrFail();

            DB::beginTransaction();

            $test->update($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Test updated successfully',
                'data' => new TestResource($test->load(['passages.questionGroups.questions', 'creator', 'class'])),
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update test',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a test from a class
     */
    public function destroy(string $classId, string $testId): JsonResponse
    {
        try {
     
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $test = Test::where('id', $testId)
                ->where('class_id', $classId)
                ->firstOrFail();

            DB::beginTransaction();

            
            Assignment::where('class_id', $classId)
                ->where('test_id', $testId)
                ->delete();

            
            $test->delete();

            DB::commit();

            return response()->json([
                'message' => 'Test deleted successfully from ' . $class->name
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign test to all students in the class
     */
    public function assignToClass(Request $request, string $classId, string $testId): JsonResponse
    {
        $request->validate([
            'due_date' => 'required|date|after:now',
            'close_date' => 'nullable|date|after:due_date',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $test = Test::where('id', $testId)
                ->where('class_id', $classId)
                ->firstOrFail();

            if (!$test->is_published) {
                throw ValidationException::withMessages([
                    'test' => ['Test must be published before assignment']
                ]);
            }

            DB::beginTransaction();

            // Create assignment
            $assignment = Assignment::create([
                'class_id' => $classId,
                'test_id' => $testId,
                'title' => $request->input('title', $test->title . ' Assignment'),
                'description' => $request->input('description', 'Complete this test by the due date'),
                'due_date' => $request->due_date,
                'close_date' => $request->close_date,
                'is_published' => true
            ]);

            // Get all active students in class
            $activeStudents = $class->enrollments()
                ->where('status', 'active')
                ->pluck('student_id');

            // Create student assignments
            $studentAssignments = [];
            foreach ($activeStudents as $studentId) {
                $studentAssignments[] = [
                    'id' => Str::uuid(),
                    'assignment_id' => $assignment->id,
                    'student_id' => $studentId,
                    'status' => 'not_started',
                    'attempt_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            StudentAssignment::insert($studentAssignments);

            DB::commit();

            return response()->json([
                'message' => 'Test assigned to all students in ' . $class->name,
                'data' => [
                    'assignment_id' => $assignment->id,
                    'test_title' => $test->title,
                    'due_date' => $assignment->due_date,
                    'assigned_to_students' => count($studentAssignments)
                ],
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to assign test',
                'error' => $e->getMessage()
            ], 422);
        }
    }



    /**
     * Helper method to create passages with questions
     */
    private function createPassageWithQuestions(Test $test, array $passageData): void
    {
        $passage = $test->passages()->create([
            'title' => $passageData['title'] ?? null,
            'description' => $passageData['description'] ?? null,
            'audio_file_path' => $passageData['audio_file_path'] ?? null,
            'transcript_type' => $passageData['transcript_type'] ?? null,
            'transcript' => $passageData['transcript'] ?? null
        ]);

        if (isset($passageData['question_groups']) && is_array($passageData['question_groups'])) {
            foreach ($passageData['question_groups'] as $groupData) {
                $questionGroup = $passage->questionGroups()->create([
                    'instruction' => $groupData['instruction'] ?? null
                ]);

                if (isset($groupData['questions']) && is_array($groupData['questions'])) {
                    foreach ($groupData['questions'] as $questionData) {
                        $question = $questionGroup->questions()->create([
                            'question_type' => $questionData['question_type'],
                            'question_number' => $questionData['question_number'] ?? null,
                            'question_text' => $questionData['question_text'] ?? null,
                            'question_data' => $questionData['question_data'] ?? null,
                            'correct_answers' => $questionData['correct_answers'] ?? null,
                            'points_value' => $questionData['points_value'] ?? 1
                        ]);

                        if (isset($questionData['options']) && is_array($questionData['options'])) {
                            foreach ($questionData['options'] as $optionData) {
                                $question->options()->create([
                                    'option_key' => $optionData['option_key'] ?? null,
                                    'option_text' => $optionData['option_text'] ?? null
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}