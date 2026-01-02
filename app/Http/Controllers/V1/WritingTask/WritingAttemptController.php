<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WritingTask\WritingAttemptResource;
use App\Models\WritingAttempt;
use App\Models\WritingTask;
use App\Models\WritingTaskQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WritingAttemptController extends Controller
{
    /**
     * Start a new writing attempt (including retakes)
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'writing_task_id' => 'required|uuid|exists:writing_tasks,id',
            'attempt_type' => 'required|in:first_attempt,whole_essay,choose_questions,specific_questions',
            'selected_questions' => 'nullable|array',
            'selected_questions.*' => 'uuid|exists:writing_task_questions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $writingTask = WritingTask::findOrFail($request->writing_task_id);
        $student = $request->user();

        // Check if retakes are allowed
        if ($request->attempt_type !== 'first_attempt') {
            if (!$writingTask->allow_retake) {
                return response()->json([
                    'message' => 'Retakes are not allowed for this task'
                ], 403);
            }

            // Check max retake attempts
            $existingAttempts = WritingAttempt::where('writing_task_id', $writingTask->id)
                ->where('student_id', $student->id)
                ->count();

            if ($writingTask->max_retake_attempts && $existingAttempts >= $writingTask->max_retake_attempts) {
                return response()->json([
                    'message' => 'Maximum retake attempts exceeded'
                ], 403);
            }
        }

        // Validate selected questions if choosing specific questions
        if ($request->attempt_type === 'choose_questions' || $request->attempt_type === 'specific_questions') {
            if (empty($request->selected_questions)) {
                return response()->json([
                    'message' => 'Selected questions are required for this attempt type'
                ], 422);
            }

            // Verify questions belong to the task
            $taskQuestions = WritingTaskQuestion::where('writing_task_id', $writingTask->id)
                ->pluck('id')
                ->toArray();

            $invalidQuestions = array_diff($request->selected_questions, $taskQuestions);
            if (!empty($invalidQuestions)) {
                return response()->json([
                    'message' => 'Some selected questions do not belong to this task'
                ], 422);
            }
        }

        // Create new attempt
        $attemptNumber = WritingAttempt::where('writing_task_id', $writingTask->id)
            ->where('student_id', $student->id)
            ->max('attempt_number') + 1;

        $attempt = WritingAttempt::create([
            'writing_task_id' => $writingTask->id,
            'student_id' => $student->id,
            'attempt_number' => $attemptNumber,
            'attempt_type' => $request->attempt_type,
            'selected_questions' => $request->selected_questions,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Writing attempt started successfully',
            'data' => new WritingAttemptResource($attempt->load(['writingTask', 'student']))
        ], 201);
    }

    /**
     * Get student's attempts for a writing task
     */
    public function getAttempts(Request $request, string $taskId): JsonResponse
    {
        $writingTask = WritingTask::findOrFail($taskId);
        $student = $request->user();

        $attempts = WritingAttempt::where('writing_task_id', $writingTask->id)
            ->where('student_id', $student->id)
            ->with(['submissions', 'feedback'])
            ->orderBy('attempt_number', 'desc')
            ->get();

        return response()->json([
            'message' => 'Attempts retrieved successfully',
            'data' => WritingAttemptResource::collection($attempts)
        ]);
    }

    /**
     * Get specific attempt details
     */
    public function show(string $attemptId): JsonResponse
    {
        $attempt = WritingAttempt::with([
            'writingTask.questions',
            'submissions.feedback',
            'submissions.question'
        ])->findOrFail($attemptId);

        return response()->json([
            'message' => 'Attempt details retrieved successfully',
            'data' => new WritingAttemptResource($attempt)
        ]);
    }

    /**
     * Submit/Complete an attempt
     */
    public function submit(Request $request, string $attemptId): JsonResponse
    {
        $attempt = WritingAttempt::findOrFail($attemptId);

        // Verify ownership
        if ($attempt->student_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized to submit this attempt'
            ], 403);
        }

        // Check if already submitted
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'message' => 'Attempt already submitted'
            ], 422);
        }

        // Calculate total time taken
        $timeTaken = null;
        if ($attempt->started_at) {
            $timeTaken = now()->diffInSeconds($attempt->started_at);
        }

        $attempt->update([
            'status' => 'submitted',
            'time_taken_seconds' => $timeTaken,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Attempt submitted successfully',
            'data' => new WritingAttemptResource($attempt->fresh())
        ]);
    }

    /**
     * Get retake options for a task
     */
    public function getRetakeOptions(string $taskId): JsonResponse
    {
        $writingTask = WritingTask::with('questions')->findOrFail($taskId);

        if (!$writingTask->allow_retake) {
            return response()->json([
                'message' => 'Retakes not allowed for this task'
            ], 403);
        }

        $retakeOptions = [
            'allow_retake' => $writingTask->allow_retake,
            'max_retake_attempts' => $writingTask->max_retake_attempts,
            'retake_options' => $writingTask->retake_options ?? [],
            'available_types' => [
                'whole_essay' => 'Rewrite Whole Essay',
                'choose_questions' => 'Choose Any Multiple Questions'
            ],
            'questions' => $writingTask->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_number' => $question->question_number,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                ];
            })
        ];

        return response()->json([
            'message' => 'Retake options retrieved successfully',
            'data' => $retakeOptions
        ]);
    }
}