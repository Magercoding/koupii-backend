<?php

namespace App\Http\Controllers\V1\SpeakingTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SpeakingTask\StartSpeakingSubmissionRequest;
use App\Http\Requests\V1\SpeakingTask\UploadRecordingRequest;
use App\Http\Requests\V1\SpeakingTask\SubmitSpeakingRequest;
use App\Http\Requests\V1\SpeakingTask\ReviewSpeakingRequest;
use App\Http\Resources\V1\SpeakingTask\SpeakingSubmissionResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingSubmissionCollection;
use App\Services\V1\SpeakingTask\SpeakingSubmissionService;
use App\Models\SpeakingTask;
use App\Models\SpeakingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpeakingSubmissionController extends Controller
{
    public function __construct(
        private SpeakingSubmissionService $speakingSubmissionService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $submissions = $this->speakingSubmissionService->getSubmissions($request->all());

        return response()->json([
            'success' => true,
            'data' => new SpeakingSubmissionCollection($submissions)
        ]);
    }

    public function startSubmission(StartSpeakingSubmissionRequest $request): JsonResponse
    {
        $taskId = $request->input('task_id');
        if (!$taskId) {
            return response()->json(['message' => 'task_id is required'], 400);
        }
        
        // Resolve task id
        $speakingTask = SpeakingTask::find($taskId);
        if (!$speakingTask) {
             $assignment = \App\Models\Assignment::find($taskId);
             if ($assignment && $assignment->task_type === 'speaking_task') {
                 $speakingTask = SpeakingTask::find($assignment->task_id);
             }
        }
        
        if (!$speakingTask) {
            $speakingTask = SpeakingTask::findOrFail($taskId); // Fallback to 404
        }
        
        // Handle assignment_id sync
        $assignmentId = $request->input('assignment_id') ?? ($speakingTask->id !== $taskId ? $taskId : null);
        if ($assignmentId && !$request->has('assignment_id')) {
            $request->merge(['assignment_id' => $assignmentId]);
        }

        $submission = $this->speakingSubmissionService->startSubmission(
            $speakingTask,
            auth()->id(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Speaking test started successfully',
            'data' => new SpeakingSubmissionResource($submission)
        ]);
    }

    public function uploadRecording(UploadRecordingRequest $request, SpeakingSubmission $submission): JsonResponse
    {
       Gate::authorize('update', $submission);

        $recording = $this->speakingSubmissionService->uploadRecording(array_merge(
            $request->validated(),
            ['submission_id' => $submission->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Recording uploaded successfully',
            'data' => $recording
        ]);
    }

    public function submitForReview(SubmitSpeakingRequest $request, SpeakingSubmission $submission): JsonResponse
    {
       Gate::authorize('update', $submission);

        $this->speakingSubmissionService->submitSpeaking($submission);

        return response()->json([
            'success' => true,
            'message' => 'Speaking test submitted successfully'
        ]);
    }

    public function show(SpeakingSubmission $submission): JsonResponse
    {
       Gate::authorize('view', $submission);

        $submission = $this->speakingSubmissionService->getSubmissionWithDetails($submission->id);

        return response()->json([
            'success' => true,
            'data' => new SpeakingSubmissionResource($submission)
        ]);
    }

    public function review(ReviewSpeakingRequest $request, SpeakingSubmission $submission): JsonResponse
    {
       Gate::authorize('review', $submission);

        $review = $this->speakingSubmissionService->reviewSubmission(
            $submission,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Speaking submission reviewed successfully',
            'data' => $review
        ]);
    }
}