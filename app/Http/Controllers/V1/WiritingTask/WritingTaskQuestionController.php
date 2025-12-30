<?php

namespace App\Http\Controllers\V1\WiritingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WritingTask\WritingTaskQuestionResource;
use App\Http\Requests\V1\WritingTask\StoreWritingTaskQuestionRequest;
use App\Http\Requests\V1\WritingTask\UpdateWritingTaskQuestionRequest;
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
    public function store(StoreWritingTaskQuestionRequest $request, WritingTask $writingTask): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get next question number
            $nextQuestionNumber = $writingTask->questions()->max('question_number') + 1;

            $validatedData = $request->validated();
            
            $question = $writingTask->questions()->create([
                'question_type' => $validatedData['question_type'],
                'question_number' => $nextQuestionNumber,
                'question_text' => $validatedData['question_text'] ?? null,
                'instructions' => $validatedData['instructions'] ?? null,
                'word_limit' => $validatedData['word_limit'] ?? null,
                'min_word_count' => $validatedData['min_word_count'] ?? null,
                'time_limit_seconds' => $validatedData['time_limit_seconds'] ?? null,
                'difficulty_level' => $validatedData['difficulty_level'] ?? null,
                'points' => $validatedData['points'] ?? 1,
                'rubric' => $validatedData['rubric'] ?? null,
                'sample_answer' => $validatedData['sample_answer'] ?? null,
                'question_data' => $validatedData['question_data'] ?? null,
                'is_required' => $validatedData['is_required'] ?? true,
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
    public function update(UpdateWritingTaskQuestionRequest $request, WritingTask $writingTask, WritingTaskQuestion $question): JsonResponse
    {
        // Verify the question belongs to the writing task
        if ($question->writing_task_id !== $writingTask->id) {
            return response()->json([
                'message' => 'Question not found for this writing task',
            ], 404);
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