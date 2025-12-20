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
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controllers\Middleware;

class WritingTestController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }


    public function index(Request $request)
    {
        $user = $request->user();

        $query = Test::query()
            ->where('type', 'writing')
            ->with(['creator']);

      
        if ($user->role === 'admin') {
            
        } elseif ($user->role === 'student') {
            // Students see only published tests
            $query->where('is_published', true);
        } else {
            // Teachers see only their own tests
            $query->where('creator_id', $user->id);
        }

        $tests = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Writing tests retrieved successfully',
            'data' => WritingTestResource::collection($tests),
        ], 200);
    }

    public function store(StoreWritingTestRequest $request, WritingTestService $service)
    {
        Gate::authorize('create', Test::class);

        try {
            $test = $service->createTest($request->validated());

            return response()->json([
                'message' => 'Writing test created successfully',
                'test_id' => $test->id,
                'data' => new WritingTestResource($test),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create writing test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $query = Test::with(['creator'])
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

            $updatedTest = (new WritingTestService())->updateTest($test, $request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Writing test updated successfully',
                'test_id' => $test->id,
                'data' => new WritingTestResource($updatedTest),
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

        try {
            $service = new WritingTestDeleteService();
            $response = $service->deleteTest($id);

            return response()->json(
                ['message' => $response['message'] ?? $response['error']],
                $response['status']
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle publish status of a test.
     */
    public function togglePublish(string $id)
    {
        $test = Test::findOrFail($id);

        Gate::authorize('update', $test);

        try {
            $test->update([
                'is_published' => !$test->is_published
            ]);

            return response()->json([
                'message' => $test->is_published ? 'Test published successfully' : 'Test unpublished successfully',
                'is_published' => $test->is_published,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle publish status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request, WritingTestService $service)
    {
        $criteria = $request->validate([
            'title' => 'nullable|string|max:255',
            'difficulty' => 'nullable|in:beginner,intermediate,advanced',
            'test_type' => 'nullable|in:academic,general,business,ielts,toefl',
            'creator_id' => 'nullable|uuid|exists:users,id',
            'is_published' => 'nullable|boolean',
        ]);

        $tests = $service->searchTests($criteria);

        return response()->json([
            'message' => 'Search results retrieved successfully',
            'data' => WritingTestResource::collection($tests),
        ], 200);
    }
}