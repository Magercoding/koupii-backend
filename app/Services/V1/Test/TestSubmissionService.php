<?php

namespace App\Services\V1\Test;

use App\Models\Test;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Models\StudentQuestionAttempt;
use App\Models\TestQuestion;
use App\Models\TestResult;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TestSubmissionService
{
    public function submitAssignment(Assignment $assignment, array $answers)
    {
        try {
            return DB::transaction(function () use ($assignment, $answers) {
                $studentId = auth()->id();
                
                // Find student assignment
                $studentAssignment = StudentAssignment::where('assignment_id', $assignment->id)
                    ->where('student_id', $studentId)
                    ->first();
                    
                if (!$studentAssignment) {
                    throw new \Exception('You are not assigned to this assignment');
                }
                
                if ($studentAssignment->status === 'completed') {
                    throw new \Exception('Assignment already completed');
                }
                
                $test = $assignment->test;
                if (!$test) {
                    throw new \Exception('No test associated with this assignment');
                }
                
                $result = $this->processAnswers($studentAssignment, $answers);
                
                // Create test result
                $testResult = TestResult::create([
                    'id' => Str::uuid(),
                    'student_assignment_id' => $studentAssignment->id,
                    'score' => $result['totalScore'],
                    'percentage' => $result['percentage'],
                    'total_correct' => $result['totalCorrect'],
                    'total_incorrect' => $result['totalIncorrect'],
                    'total_unanswered' => 0,
                ]);
                
                // Update student assignment status
                $studentAssignment->update([
                    'status' => 'completed',
                    'score' => $result['totalScore'],
                    'completed_at' => now(),
                ]);
                
                return [
                    'assignment' => $assignment,
                    'student_assignment' => $studentAssignment,
                    'test_result' => $testResult,
                    'test' => $test,
                    'statistics' => $result,
                ];
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to submit assignment: ' . $e->getMessage());
        }
    }

    public function getAssignmentResults(Assignment $assignment)
    {
        $studentId = auth()->id();
        
        $studentAssignments = StudentAssignment::where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->with(['testResult', 'questionAttempts.question'])
            ->get();
            
        if ($studentAssignments->isEmpty()) {
            throw new \Exception('No completed submissions found for this assignment');
        }
        
        return $this->formatResults($studentAssignments, $assignment);
    }

    public function getTestAttempt(Test $test)
    {
        $studentId = auth()->id();
        
        // For direct test access (not through assignment)
        // This might be for practice tests or public tests
        
        $attemptCount = $this->getAttemptCount($test, $studentId);
        
        if (!$test->allow_repetition && $attemptCount > 0) {
            throw new \Exception('You have already submitted this test and repetition is not allowed');
        }
        
        if ($test->max_repetition_count && $attemptCount >= $test->max_repetition_count) {
            throw new \Exception('Maximum number of attempts reached');
        }
        
        return [
            'test' => $test->load(['passages.questionGroups.questions.options']),
            'attempt_info' => [
                'can_attempt' => true,
                'attempts_used' => $attemptCount,
                'max_attempts' => $test->max_repetition_count,
            ],
        ];
    }

    public function checkAnswer($question, $studentAnswer)
    {
        if (!$question->correct_answers) {
            return false;
        }
        
        $correctAnswers = is_array($question->correct_answers) 
            ? $question->correct_answers 
            : json_decode($question->correct_answers, true);
            
        if (!$correctAnswers) {
            return false;
        }
        
        // Handle different question types
        switch ($question->question_type) {
            case 'multiple_choice':
                return in_array($studentAnswer, $correctAnswers);
                
            case 'multiple_answer':
                $studentAnswerArray = is_array($studentAnswer) ? $studentAnswer : [$studentAnswer];
                return empty(array_diff($studentAnswerArray, $correctAnswers)) && 
                       empty(array_diff($correctAnswers, $studentAnswerArray));
                       
            case 'text_input':
            case 'short_answer':
                return in_array(strtolower(trim($studentAnswer)), 
                    array_map('strtolower', array_map('trim', $correctAnswers)));
                    
            default:
                return strtolower(trim($studentAnswer)) === strtolower(trim($correctAnswers[0] ?? ''));
        }
    }

    private function processAnswers(StudentAssignment $studentAssignment, array $answers)
    {
        $totalScore = 0;
        $maxScore = 0;
        $totalCorrect = 0;
        $totalIncorrect = 0;
        
        foreach ($answers as $answerData) {
            $question = TestQuestion::find($answerData['question_id']);
            if (!$question) {
                continue;
            }
            
            $isCorrect = $this->checkAnswer($question, $answerData['answer']);
            $pointsEarned = $isCorrect ? $question->points_value : 0;
            
            StudentQuestionAttempt::create([
                'id' => Str::uuid(),
                'student_assignment_id' => $studentAssignment->id,
                'question_id' => $question->id,
                'selected_answer' => $answerData['answer'],
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'time_spent_seconds' => $answerData['time_taken'] ?? null,
            ]);
            
            $totalScore += $pointsEarned;
            $maxScore += $question->points_value;
            
            if ($isCorrect) {
                $totalCorrect++;
            } else {
                $totalIncorrect++;
            }
        }
        
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
        
        return [
            'totalScore' => $totalScore,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'totalCorrect' => $totalCorrect,
            'totalIncorrect' => $totalIncorrect,
            'totalQuestions' => count($answers),
        ];
    }

    private function formatResults($studentAssignments, $assignment)
    {
        $submissions = $studentAssignments->map(function ($assignment) {
            $result = $assignment->testResult;
            return [
                'assignment_id' => $assignment->assignment_id,
                'student_assignment_id' => $assignment->id,
                'result_id' => $result->id,
                'total_score' => $result->score,
                'percentage' => $result->percentage,
                'total_correct' => $result->total_correct,
                'total_incorrect' => $result->total_incorrect,
                'total_unanswered' => $result->total_unanswered,
                'completed_at' => $assignment->completed_at,
                'answers' => $assignment->questionAttempts->map(function ($attempt) {
                    return [
                        'question_id' => $attempt->question_id,
                        'question_text' => $attempt->question->question_text,
                        'question_type' => $attempt->question->question_type,
                        'student_answer' => $attempt->selected_answer,
                        'correct_answer' => $attempt->question->correct_answers,
                        'is_correct' => $attempt->is_correct,
                        'points_earned' => $attempt->points_earned,
                        'max_points' => $attempt->question->points_value,
                        'time_spent' => $attempt->time_spent_seconds,
                    ];
                }),
            ];
        });
        
        return [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'due_date' => $assignment->due_date,
            ],
            'submissions' => $submissions,
        ];
    }

    private function getAttemptCount(Test $test, $studentId)
    {
        // This would need to be implemented based on how you track direct test attempts
        // For now, returning 0 as this might be for practice/public tests
        return 0;
    }
}