<?php

namespace App\Http\Controllers\V1\Test;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Assignment;
use App\Http\Requests\V1\Test\StoreTestSubmissionRequest;
use App\Services\V1\Test\TestSubmissionService;
use Illuminate\Http\Request;

class TestSubmissionController extends Controller
{
    protected TestSubmissionService $submissionService;

    public function __construct(TestSubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    /**
     * Submit answers for an assignment
     */
    public function submitAssignment(StoreTestSubmissionRequest $request, Assignment $assignment)
    {
        try {
            $result = $this->submissionService->submitAssignment($assignment, $request->answers);

            return response()->json([
                'message' => 'Assignment submitted successfully',
                'data' => [
                    'assignment_id' => $result['assignment']->id,
                    'student_assignment_id' => $result['student_assignment']->id,
                    'result_id' => $result['test_result']->id,
                    'test_id' => $result['test']->id,
                    'student_id' => auth()->id(),
                    'total_score' => $result['statistics']['totalScore'],
                    'max_score' => $result['statistics']['maxScore'],
                    'percentage' => $result['statistics']['percentage'],
                    'total_questions' => $result['statistics']['totalQuestions'],
                    'correct_answers' => $result['statistics']['totalCorrect'],
                    'incorrect_answers' => $result['statistics']['totalIncorrect'],
                    'submitted_at' => now(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get student's assignment results
     */
    public function assignmentResults(Assignment $assignment)
    {
        try {
            $results = $this->submissionService->getAssignmentResults($assignment);
            
            return response()->json([
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch results',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get test attempt for student (to continue or start)
     */
    public function attempt(Test $test)
    {
        try {
            $attemptData = $this->submissionService->getTestAttempt($test);
            
            return response()->json([
                'data' => [
                    'test' => [
                        'id' => $test->id,
                        'title' => $test->title,
                        'description' => $test->description,
                        'type' => $test->type,
                        'difficulty' => $test->difficulty,
                        'timer_mode' => $test->timer_mode,
                        'timer_settings' => $test->timer_settings,
                        'allow_repetition' => $test->allow_repetition,
                        'max_repetition_count' => $test->max_repetition_count,
                    ],
                    'passages' => $attemptData['test']->passages->map(function ($passage) {
                        return [
                            'id' => $passage->id,
                            'title' => $passage->title,
                            'description' => $passage->description,
                            'audio_file_path' => $passage->audio_file_path,
                            'transcript' => $passage->transcript,
                            'question_groups' => $passage->questionGroups->map(function ($group) {
                                return [
                                    'id' => $group->id,
                                    'instruction' => $group->instruction,
                                    'questions' => $group->questions->map(function ($question) {
                                        return [
                                            'id' => $question->id,
                                            'question_type' => $question->question_type,
                                            'question_number' => $question->question_number,
                                            'question_text' => $question->question_text,
                                            'question_data' => $question->question_data,
                                            'points_value' => $question->points_value,
                                            'options' => $question->options->map(function ($option) {
                                                return [
                                                    'id' => $option->id,
                                                    'option_key' => $option->option_key,
                                                    'option_text' => $option->option_text,
                                                ];
                                            }),
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                    'attempt_info' => $attemptData['attempt_info'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get test attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}