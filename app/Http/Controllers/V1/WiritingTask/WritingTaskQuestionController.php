<?php

namespace App\Http\Controllers\V1\WiritingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WritingTask\WritingTaskQuestionResource;
use App\Models\WritingTask;
use App\Models\WritingTaskQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WritingTaskQuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Display questions for a specific writing task.
     */
    public function index(Request $request, WritingTask $writingTask): JsonResponse
    {
        $questions = $writingTask->questions()
            ->with('resources')
            ->orderBy('question_number')
            ->get();

        return response()->json([
            'message' => 'Writing task questions retrieved successfully',
            'data' => WritingTaskQuestionResource::collection($questions),
        ]);
    }

    /**
     * Store a new question for a writing task.
     */
    public function store(Request $request, WritingTask $writingTask): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_type' => ['required', 'string', Rule::in(WritingTaskQuestion::QUESTION_TYPES)],
            'question_text' => 'required|string|max:2000',
            'instructions' => 'nullable|string|max:1000',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'min_word_count' => 'nullable|integer|min:10|max:4000',
            'time_limit_seconds' => 'nullable|integer|min:60|max:7200',
            'difficulty_level' => ['nullable', Rule::in(WritingTaskQuestion::DIFFICULTY_LEVELS)],
            'points' => 'nullable|numeric|min:0|max:100',
            'rubric' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'question_data' => 'nullable|array',
            'is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get next question number
            $nextQuestionNumber = $writingTask->questions()->max('question_number') + 1;

            $question = $writingTask->questions()->create([
                'question_type' => $request->question_type,
                'question_number' => $nextQuestionNumber,
                'question_text' => $request->question_text,
                'instructions' => $request->instructions,
                'word_limit' => $request->word_limit,
                'min_word_count' => $request->min_word_count,
                'time_limit_seconds' => $request->time_limit_seconds,
                'difficulty_level' => $request->difficulty_level,
                'points' => $request->points ?? 1,
                'rubric' => $request->rubric,
                'sample_answer' => $request->sample_answer,
                'question_data' => $request->question_data,
                'is_required' => $request->is_required ?? true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Question created successfully',
                'data' => new WritingTaskQuestionResource($question),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific question.
     */
    public function show(WritingTask $writingTask, WritingTaskQuestion $question): JsonResponse
    {
        // Verify the question belongs to the writing task
        if ($question->writing_task_id !== $writingTask->id) {
            return response()->json([
                'message' => 'Question not found for this writing task',
            ], 404);
        }

        $question->load('resources');

        return response()->json([
            'message' => 'Question retrieved successfully',
            'data' => new WritingTaskQuestionResource($question),
        ]);
    }

    /**
     * Update a specific question.
     */
    public function update(Request $request, WritingTask $writingTask, WritingTaskQuestion $question): JsonResponse
    {
        // Verify the question belongs to the writing task
        if ($question->writing_task_id !== $writingTask->id) {
            return response()->json([
                'message' => 'Question not found for this writing task',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question_type' => ['sometimes', 'string', Rule::in(WritingTaskQuestion::QUESTION_TYPES)],
            'question_text' => 'sometimes|string|max:2000',
            'instructions' => 'nullable|string|max:1000',
            'word_limit' => 'nullable|integer|min:50|max:5000',
            'min_word_count' => 'nullable|integer|min:10|max:4000',
            'time_limit_seconds' => 'nullable|integer|min:60|max:7200',
            'difficulty_level' => ['nullable', Rule::in(WritingTaskQuestion::DIFFICULTY_LEVELS)],
            'points' => 'nullable|numeric|min:0|max:100',
            'rubric' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'question_data' => 'nullable|array',
            'is_required' => 'boolean',
            'question_number' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $question->update($request->only([
                'question_type',
                'question_text',
                'instructions',
                'word_limit',
                'min_word_count',
                'time_limit_seconds',
                'difficulty_level',
                'points',
                'rubric',
                'sample_answer',
                'question_data',
                'is_required',
                'question_number',
            ]));

            DB::commit();

            return response()->json([
                'message' => 'Question updated successfully',
                'data' => new WritingTaskQuestionResource($question),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to update question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific question.
     */
    public function destroy(WritingTask $writingTask, WritingTaskQuestion $question): JsonResponse
    {
        // Verify the question belongs to the writing task
        if ($question->writing_task_id !== $writingTask->id) {
            return response()->json([
                'message' => 'Question not found for this writing task',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete question resources first
            $question->resources()->delete();
            
            // Delete the question
            $question->delete();

            // Reorder remaining questions
            $remainingQuestions = $writingTask->questions()
                ->orderBy('question_number')
                ->get();

            foreach ($remainingQuestions as $index => $q) {
                $q->update(['question_number' => $index + 1]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Question deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to delete question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder questions within a writing task.
     */
    public function reorder(Request $request, WritingTask $writingTask): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'questions' => 'required|array',
            'questions.*.id' => 'required|uuid|exists:writing_task_questions,id',
            'questions.*.question_number' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->questions as $questionData) {
                WritingTaskQuestion::where('id', $questionData['id'])
                    ->where('writing_task_id', $writingTask->id)
                    ->update(['question_number' => $questionData['question_number']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Questions reordered successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to reorder questions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}