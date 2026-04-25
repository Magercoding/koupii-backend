<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Test\StoreTestRequest;
use App\Http\Requests\V1\Test\UpdateTestRequest;
use App\Http\Resources\V1\Test\TestResource;
use App\Services\V1\Test\TestService;
use App\Models\StudentAssignment;
use App\Models\Test;
use App\Models\ReadingTask;
use App\Models\ListeningTask;
use App\Models\SpeakingTask;
use App\Models\WritingTask;
use App\Models\Classes;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Events\TestAssignedToClass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassTestController extends Controller
{
    protected TestService $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }
    /**
     * Get all tests for a specific class
     */
    public function index(Request $request, string $classId): JsonResponse
    {
        try {
            // Verify class access (owner or enrolled teacher)
            $class = Classes::where('id', $classId)->first();
            
            if (!$class) {
                return response()->json([
                    'message' => 'Class not found',
                    'error' => 'Class does not exist'
                ], 404);
            }

            $user = auth()->user();
            $isOwner = $class->teacher_id === $user->id;
            $isEnrolledTeacher = $user->role === 'teacher' && ClassEnrollment::where('class_id', $classId)
                ->where('student_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if (!$isOwner && !$isEnrolledTeacher && $user->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized',
                    'error' => 'You do not have access to this class'
                ], 403);
            }

            // Legacy tests (class-specific + public)
            $tests = Test::where(function ($q) use ($classId) {
                    $q->where('class_id', $classId)
                      ->orWhere('is_public', true);
                })
                ->with(['passages.questionGroups.questions', 'creator'])
                ->when($request->get('type'), function ($query, $type) {
                    return $query->where('type', $type);
                })
                ->when($request->get('is_published'), function ($query, $published) {
                    return $query->where('is_published', $published);
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($t) => (new TestResource($t))->resolve());

            // New task-based tests created with class_id (not yet assigned)
            $taskType = $request->get('type');
            $taskPublished = $request->get('is_published');

            $readingTasks = ReadingTask::query()
                ->where('class_id', $classId)
                ->when($taskType, fn ($q) => $q->where('task_type', $taskType))
                ->when($taskPublished !== null, fn ($q) => $q->where('is_published', (bool) $taskPublished))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title ?? 'Untitled',
                    'description' => $t->description,
                    'type' => 'reading',
                    'difficulty' => $t->difficulty_level ?? $t->difficulty ?? 'beginner',
                    'test_type' => 'single',
                    'timer_mode' => $t->timer_type ?? 'none',
                    'timer_settings' => null,
                    'allow_repetition' => (bool) ($t->allow_retake ?? false),
                    'max_repetition_count' => $t->max_retake_attempts,
                    'is_public' => false,
                    'is_published' => (bool) $t->is_published,
                    'settings' => null,
                    'due_date' => optional($t->due_date)->toISOString(),
                    'created_at' => optional($t->created_at)->toISOString(),
                    'updated_at' => optional($t->updated_at)->toISOString(),
                ]);

            $listeningTasks = ListeningTask::query()
                ->where('class_id', $classId)
                ->when($taskType, fn ($q) => $q->where('task_type', $taskType))
                ->when($taskPublished !== null, fn ($q) => $q->where('is_published', (bool) $taskPublished))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title ?? 'Untitled',
                    'description' => $t->description,
                    'type' => 'listening',
                    'difficulty' => $t->difficulty_level ?? $t->difficulty ?? 'beginner',
                    'test_type' => 'single',
                    'timer_mode' => $t->timer_type ?? 'none',
                    'timer_settings' => null,
                    'allow_repetition' => (bool) ($t->allow_retake ?? false),
                    'max_repetition_count' => $t->max_retake_attempts,
                    'is_public' => false,
                    'is_published' => (bool) $t->is_published,
                    'settings' => null,
                    'due_date' => optional($t->due_date)->toISOString(),
                    'created_at' => optional($t->created_at)->toISOString(),
                    'updated_at' => optional($t->updated_at)->toISOString(),
                ]);

            $speakingTasks = SpeakingTask::query()
                ->where('class_id', $classId)
                ->when($taskPublished !== null, fn ($q) => $q->where('is_published', (bool) $taskPublished))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title ?? 'Untitled',
                    'description' => $t->description,
                    'type' => 'speaking',
                    'difficulty' => $t->difficulty_level ?? 'beginner',
                    'test_type' => 'single',
                    'timer_mode' => $t->timer_type ?? 'none',
                    'timer_settings' => null,
                    'allow_repetition' => false,
                    'max_repetition_count' => null,
                    'is_public' => false,
                    'is_published' => (bool) $t->is_published,
                    'settings' => null,
                    'due_date' => optional($t->due_date ?? null)->toISOString(),
                    'created_at' => optional($t->created_at)->toISOString(),
                    'updated_at' => optional($t->updated_at)->toISOString(),
                ]);

            $writingTasks = WritingTask::query()
                ->where('class_id', $classId)
                ->when($taskType, fn ($q) => $q->where('task_type', $taskType))
                ->when($taskPublished !== null, fn ($q) => $q->where('is_published', (bool) $taskPublished))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title ?? 'Untitled',
                    'description' => $t->description,
                    'type' => 'writing',
                    'difficulty' => $t->difficulty ?? 'beginner',
                    'test_type' => 'single',
                    'timer_mode' => $t->timer_type ?? 'none',
                    'timer_settings' => null,
                    'allow_repetition' => (bool) ($t->allow_retake ?? false),
                    'max_repetition_count' => $t->max_retake_attempts,
                    'is_public' => false,
                    'is_published' => (bool) $t->is_published,
                    'settings' => null,
                    'due_date' => optional($t->due_date)->toISOString(),
                    'created_at' => optional($t->created_at)->toISOString(),
                    'updated_at' => optional($t->updated_at)->toISOString(),
                ]);

            $all = collect()
                ->concat($tests)
                ->concat($readingTasks)
                ->concat($listeningTasks)
                ->concat($speakingTasks)
                ->concat($writingTasks)
                ->sortByDesc(fn ($t) => $t['created_at'] ?? null)
                ->values()
                ->all();

            return response()->json([
                'message' => 'Class tests retrieved successfully',
                'data' => $all,
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

            $testData = $request->validated();
            $testData['class_id'] = $classId;
            $testData['creator_id'] = auth()->id();

            // Use TestService to create the test (this will trigger automatic assignment creation)
            $test = $this->testService->createTest($testData);

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
            // Verify class ownership
            $class = Classes::where('id', $classId)
                ->where('teacher_id', auth()->id())
                ->firstOrFail();

            $test = Test::where('id', $testId)
                ->where('class_id', $classId)
                ->firstOrFail();

            $testData = $request->validated();
            $testData['class_id'] = $classId; // Ensure class_id is maintained

            // Use TestService to update the test (this will trigger automatic assignment creation if needed)
            $updatedTest = $this->testService->updateTest($test, $testData);

            return response()->json([
                'message' => 'Test updated successfully',
                'data' => new TestResource($updatedTest->load(['passages.questionGroups.questions', 'creator', 'class'])),
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name
                ]
            ]);

        } catch (\Exception $e) {
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

            // Prepare assignment options
            $options = [
                'title' => $request->input('title', $test->title . ' Assignment'),
                'description' => $request->input('description', 'Complete this test by the due date'),
                'due_date' => $request->due_date,
                'close_date' => $request->close_date,
                'is_published' => true
            ];

            // Dispatch event to trigger automatic assignment creation
            TestAssignedToClass::dispatch($test, $class, $options);

            // Get student count for response
            $studentCount = $class->enrollments()
                ->where('status', 'active')
                ->count();

            return response()->json([
                'message' => 'Test assigned to all students in ' . $class->name,
                'data' => [
                    'test_id' => $test->id,
                    'test_title' => $test->title,
                    'due_date' => $options['due_date'],
                    'assigned_to_students' => $studentCount
                ],
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name
                ]
            ]);

        } catch (\Exception $e) {
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