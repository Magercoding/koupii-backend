<?php

namespace App\Http\Controllers\V1\WritingTest;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\WritingTest\StoreWritingTestRequest;
use App\Http\Requests\V1\WritingTest\UpdateWritingTestRequest;
use App\Http\Resources\V1\WritingTest\WritingTestResource;
use App\Models\Test;
use App\Services\V1\WritingTest\WritingTestService;
use App\Services\V1\WritingTest\WritingTestDeleteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WritingTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Test::query()
            ->where('type', 'writing')
            ->with(['creator', 'writingPrompts.criteria']);

        // Role-based access control
        if ($user->role === 'admin') {
            // Admin sees all tests
        } elseif ($user->role === 'student') {
            // Students see only published tests
            $query->where('is_published', true);
        } else {
            // Teachers see only their own tests
            $query->where('creator_id', $user->id);
        }

        $tests = $query->get();

        return response()->json([
            'message' => 'Writing tests retrieved successfully',
            'data' => WritingTestResource::collection($tests),
        ], 200);
    }

    public function store(StoreWritingTestRequest $request, WritingTestService $service)
    {
        Gate::authorize('create', Test::class);

        $test = $service->create($request->validated(), $request);

        return response()->json([
            'message' => 'Writing test created successfully',
            'test_id' => $test->id,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $query = Test::with(['creator', 'writingPrompts.criteria'])
            ->where('type', 'writing')
            ->where('id', $id);

        // Role-based access control
        if ($user->role === 'student') {
            $query->where('is_published', true);
        } elseif ($user->role !== 'admin') {
            $query->where('creator_id', $user->id);
        }

        $test = $query->first();

        if (!$test) {
            return response()->json([
                'message' => 'Test not found or unauthorized access',
            ], 404);
        }

        Gate::authorize('view', $test);

        return new WritingTestResource($test);
    }

    public function update(UpdateWritingTestRequest $request, string $id)
    {
        $test = Test::findOrFail($id);

        Gate::authorize('update', $test);

        try {
            DB::beginTransaction();

            (new WritingTestService())->updateTest($test, $request->validated(), $request);

            DB::commit();

            return response()->json([
                'message' => 'Writing test updated successfully',
                'test_id' => $test->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $test = Test::findOrFail($id);

        Gate::authorize('delete', $test);

        $service = new WritingTestDeleteService();
        $response = $service->deleteTest($id);

        return response()->json(
            ['message' => $response['message'] ?? $response['error']],
            $response['status']
        );
    }

    public function deletePrompt(string $id)
    {
        $service = new WritingTestDeleteService();
        $response = $service->deletePrompt($id);

        return response()->json(
            ['message' => $response['message'] ?? $response['error']],
            $response['status']
        );
    }
}