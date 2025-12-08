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
use App\Models\Test;
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

    public function start(StartSpeakingSubmissionRequest $request, Test $test): JsonResponse
    {
        $submission = $this->speakingSubmissionService->startSubmission(
            $test,
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

        $recording = $this->speakingSubmissionService->uploadRecording(
            $submission,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Recording uploaded successfully',
            'data' => $recording
        ]);
    }

    public function submit(SubmitSpeakingRequest $request, SpeakingSubmission $submission): JsonResponse
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
            auth()->id(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Speaking submission reviewed successfully',
            'data' => $review
        ]);
    }
}