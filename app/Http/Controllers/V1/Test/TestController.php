<?php

namespace App\Http\Controllers\V1\Test;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Test;
use App\Http\Requests\V1\Test\StoreTestRequest;
use App\Http\Requests\V1\Test\UpdateTestRequest;
use App\Http\Resources\V1\Test\TestResource;
use App\Http\Resources\V1\Test\TestCollection;
use App\Services\V1\Test\TestService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected TestService $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    /**
     * Display a listing of the tests.
     * Filters tests based on class association and user permissions
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['type', 'difficulty', 'is_published', 'search', 'class_id', 'scope']);
            
            // Add scope filter for test type
            $scope = $request->get('scope', 'all'); // 'global', 'class', 'all'
            if ($scope === 'global') {
                $filters['class_id'] = null; // Only global tests
            } elseif ($scope === 'class') {
                $filters['has_class'] = true; // Only class-based tests
            }
            
            $query = $this->testService->getTestsForUser($filters);
            $tests = $query->paginate($request->get('per_page', 15));

            return new TestCollection($tests);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created test.
     * Supports both global tests (legacy) and class-based tests (new system)
     */
    public function store(StoreTestRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            // Add creator_id if not present
            $validatedData['creator_id'] = auth()->id();
            
            // Support class-based test creation
            if ($request->has('class_id')) {

                $class = Classes::findOrFail($request->class_id);
                if (auth()->user()->role !== 'admin' && $class->teacher_id !== auth()->id()) {
                    return response()->json(['message' => 'Unauthorized to create tests for this class'], 403);
                }
            }
            
            $test = $this->testService->createTest($validatedData);

            return response()->json([
                'message' => 'Test created successfully',
                'data' => new TestResource($test),
                'type' => $request->has('class_id') ? 'class_test' : 'global_test'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified test.
     */
    public function show(Test $test)
    {
        try {
            $test = $this->testService->getTestWithQuestions($test);
            
            return response()->json([
                'data' => new TestResource($test)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified test.
     */
    public function update(UpdateTestRequest $request, Test $test)
    {
        try {
            // Check authorization
            if ($test->creator_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $test = $this->testService->updateTest($test, $request->validated());

            return response()->json([
                'message' => 'Test updated successfully',
                'data' => new TestResource($test)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified test.
     */
    public function destroy(Test $test)
    {
        try {
            $this->testService->deleteTest($test);

            return response()->json([
                'message' => 'Test deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a test
     */
    public function duplicate(Test $test)
    {
        try {
            $newTest = $this->testService->duplicateTest($test);

            return response()->json([
                'message' => 'Test duplicated successfully',
                'data' => new TestResource($newTest)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to duplicate test',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}