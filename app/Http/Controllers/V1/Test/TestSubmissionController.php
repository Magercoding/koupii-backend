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
                     'class_id' => $result['test']->class_id,
                    'student_id' => auth()->id(),
                    'total_score' => $result['statistics']['totalScore'],
                    'max_score' => $result['statistics']['maxScore'],
                    'percentage' => $result['statistics']['percentage'],
                    'total_questions' => $result['statistics']['totalQuestions'],
                    'correct_answers' => $result['statistics']['totalCorrect'],
                    'incorrect_answers' => $result['statistics']['totalIncorrect'],
                    'submitted_at' => now(),
                    'submission_type' => $result['test']->class_id ? 'class_assignment' : 'global_test'
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
     * Validates class enrollment for class-based tests
     */
    public function attempt(Test $test)
    {
        try {
            // Validate access for class-based tests
            if ($test->class_id && !$test->is_public) {
                $user = auth()->user();
                
                // Check if student is enrolled in the class
                if ($user->role === 'student') {
                    $enrollment = \App\Models\ClassEnrollment::where([
                        'class_id' => $test->class_id,
                        'student_id' => $user->id,
                        'status' => 'active'
                    ])->first();
                    
                    if (!$enrollment) {
                        return response()->json([
                            'message' => 'You are not enrolled in this class',
                            'error' => 'Class enrollment required'
                        ], 403);
                    }
                }
                // Teachers and admins can access any class test
                elseif ($user->role === 'teacher') {
                    $class = \App\Models\Classes::find($test->class_id);
                    if ($class && $class->teacher_id !== $user->id && $user->role !== 'admin') {
                        return response()->json([
                            'message' => 'Unauthorized to access this class test',
                            'error' => 'Insufficient permissions'
                        ], 403);
                    }
                }
            }
            
            $attemptData = $this->submissionService->getTestAttempt($test);
            
            // Use TestResource to handle complex mapping consistently
            $testResource = (new \App\Http\Resources\V1\Test\TestResource($test))->resolve();
            
            return response()->json([
                'data' => array_merge($testResource, [
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
                    // Keep compatibility with legacy frontend structure that expects root-level passages
                    'passages' => $testResource['passages'] ?? [],
                    'speaking_sections' => $testResource['speaking_sections'] ?? [],
                    'audio_segments' => $testResource['audio_segments'] ?? [],
                    'writing_tasks' => $testResource['writing_tasks'] ?? [],
                    'attempt_info' => $attemptData['attempt_info'],
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get test attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}