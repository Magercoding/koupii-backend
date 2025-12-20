<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SpeakingTask\StoreSpeakingTaskRequest;
use App\Http\Requests\V1\SpeakingTask\UpdateSpeakingTaskRequest;
use App\Http\Requests\V1\SpeakingTask\AssignSpeakingTaskRequest;
use App\Http\Resources\V1\SpeakingTask\SpeakingTaskResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingTaskCollection;
use App\Services\V1\SpeakingTask\SpeakingTaskService;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpeakingTaskController extends Controller
{
    public function __construct(
        private SpeakingTaskService $speakingTaskService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tests = $this->speakingTaskService->getTeacherSpeakingTasks(
            auth()->id(),
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => new SpeakingTaskCollection($tests)
        ]);
    }

    public function store(StoreSpeakingTaskRequest $request): JsonResponse
    {
        $test = $this->speakingTaskService->createSpeakingTask($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Speaking task created successfully',
            'data' => new SpeakingTaskResource($test)
        ], 201);
    }

    public function show(Test $test): JsonResponse
    {
        Gate::authorize('view', $test);

        $test = $this->speakingTaskService->getSpeakingTaskWithDetails($test->id);

        return response()->json([
            'success' => true,
            'data' => new SpeakingTaskResource($test)
        ]);
    }

    public function update(UpdateSpeakingTaskRequest $request, Test $test): JsonResponse
    {
        Gate::authorize('update', $test);

        $test = $this->speakingTaskService->updateSpeakingTask($test, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Speaking task updated successfully',
            'data' => new SpeakingTaskResource($test)
        ]);
    }

    public function destroy(Test $test): JsonResponse
    {
        Gate::authorize('delete', $test);

        $this->speakingTaskService->deleteSpeakingTask($test);

        return response()->json([
            'success' => true,
            'message' => 'Speaking task deleted successfully'
        ]);
    }

    public function assign(AssignSpeakingTaskRequest $request, Test $test): JsonResponse
    {
        Gate::authorize('update', $test);

        $this->speakingTaskService->assignToClasses($test, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Speaking task assigned successfully'
        ]);
    }

    public function sendOut(Test $test): JsonResponse
    {
        Gate::authorize('update', $test);

        $this->speakingTaskService->publishTask($test);

        return response()->json([
            'success' => true,
            'message' => 'Speaking task sent out successfully'
        ]);
    }
}