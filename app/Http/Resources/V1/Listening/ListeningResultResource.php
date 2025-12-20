<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'student_id' => $this->student_id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status,
            'score' => $this->score,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'total_questions' => $this->total_questions,
            'correct_answers' => $this->correct_answers,
            'incorrect_answers' => $this->incorrect_answers,
            'unanswered_questions' => $this->unanswered_questions,
            'time_spent_minutes' => $this->time_spent_minutes,
            'started_at' => $this->started_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            
            // Test information
            'test' => [
                'title' => $this->test->title,
                'duration_minutes' => $this->test->duration_minutes,
                'total_points' => $this->test->testQuestions()->sum('points'),
                'passing_score' => $this->test->passing_score ?? 70
            ],
            
            // Performance metrics
            'performance' => [
                'accuracy_percentage' => $this->getAccuracyPercentage(),
                'time_efficiency' => $this->getTimeEfficiency(),
                'listening_efficiency' => $this->getListeningEfficiency(),
                'comprehension_score' => $this->getComprehensionScore(),
                'response_consistency' => $this->getResponseConsistency()
            ],
            
            // Audio engagement metrics
            'audio_engagement' => [
                'total_audio_plays' => array_sum($this->audio_play_counts ?? []),
                'unique_segments_played' => count($this->audio_play_counts ?? []),
                'average_plays_per_segment' => $this->getAveragePlayCount(),
                'most_replayed_segments' => $this->getMostReplayedSegments(),
                'listening_patterns' => $this->getListeningPatterns()
            ],
            
            // Question type breakdown
            'question_analysis' => $this->getQuestionTypeAnalysis(),
            
            // Detailed answers (if requested)
            'detailed_answers' => $this->when($request->get('include_answers'), function () {
                return $this->answers->map(function ($answer) {
                    return [
                        'question_id' => $answer->question_id,
                        'question_type' => $answer->question->question_type,
                        'question_text' => $answer->question->question_text,
                        'is_correct' => $answer->is_correct,
                        'points_earned' => $answer->points_earned,
                        'time_spent' => $answer->time_spent_seconds,
                        'play_count' => $answer->play_count,
                        'student_answer' => $this->formatStudentAnswer($answer),
                        'correct_answer' => $this->formatCorrectAnswer($answer->question),
                        'explanation' => $answer->answer_explanation,
                        'difficulty_assessment' => $this->assessQuestionDifficulty($answer)
                    ];
                });
            }),
            
            // Recommendations
            'recommendations' => [
                'strengths' => $this->getStrengths(),
                'areas_for_improvement' => $this->getAreasForImprovement(),
                'study_suggestions' => $this->getStudySuggestions(),
                'next_steps' => $this->getNextSteps()
            ],
            
            // Comparison metrics
            'benchmarks' => [
                'class_average' => $this->getClassAverage(),
                'percentile_ranking' => $this->getPercentileRanking(),
                'improvement_from_last_attempt' => $this->getImprovementFromLastAttempt()
            ]
        ];
    }
    
    /**
     * Calculate accuracy percentage
     */
    private function getAccuracyPercentage(): float
    {
        if ($this->total_questions === 0) return 0;
        return round(($this->correct_answers / $this->total_questions) * 100, 1);
    }
    
    /**
     * Calculate time efficiency
     */
    private function getTimeEfficiency(): float
    {
        $testDuration = $this->test->duration_minutes ?? 60;
        $timeUsed = $this->time_spent_minutes ?? $testDuration;
        
        if ($timeUsed === 0) return 100;
        
        // Efficiency = (optimal time / time used) * 100
        $optimalTime = $testDuration * 0.8; // 80% of test time is optimal
        return round(min(100, ($optimalTime / $timeUsed) * 100), 1);
    }
    
    /**
     * Calculate listening efficiency
     */
    private function getListeningEfficiency(): float
    {
        $totalPlays = array_sum($this->audio_play_counts ?? []);
        $uniqueSegments = count($this->audio_play_counts ?? []);
        
        if ($uniqueSegments === 0) return 0;
        
        $averagePlays = $totalPlays / $uniqueSegments;
        // 1-2 plays = 100%, 3 plays = 75%, 4 plays = 50%, 5+ plays = 25%
        return max(25, 125 - ($averagePlays * 25));
    }
    
    /**
     * Calculate comprehension score
     */
    private function getComprehensionScore(): float
    {
        $accuracy = $this->getAccuracyPercentage();
        $listeningEfficiency = $this->getListeningEfficiency();
        
        // Weighted score: 70% accuracy, 30% listening efficiency
        return round(($accuracy * 0.7) + ($listeningEfficiency * 0.3), 1);
    }
    
    /**
     * Calculate response consistency
     */
    private function getResponseConsistency(): float
    {
        // Analyze variation in response times and play counts
        $answers = $this->answers;
        if ($answers->count() < 3) return 100;
        
        $timesSpent = $answers->pluck('time_spent_seconds')->filter()->toArray();
        $playCounts = $answers->pluck('play_count')->filter()->toArray();
        
        if (empty($timesSpent) || empty($playCounts)) return 100;
        
        $timeVariation = $this->calculateCoefficientsOfVariation($timesSpent);
        $playVariation = $this->calculateCoefficientsOfVariation($playCounts);
        
        // Lower variation = higher consistency
        $consistency = 100 - (($timeVariation + $playVariation) * 50);
        return round(max(0, min(100, $consistency)), 1);
    }
    
    /**
     * Get average play count per segment
     */
    private function getAveragePlayCount(): float
    {
        $playCounts = $this->audio_play_counts ?? [];
        if (empty($playCounts)) return 0;
        
        return round(array_sum($playCounts) / count($playCounts), 1);
    }
    
    /**
     * Get most replayed segments
     */
    private function getMostReplayedSegments(): array
    {
        $playCounts = $this->audio_play_counts ?? [];
        arsort($playCounts);
        
        return array_slice($playCounts, 0, 3, true);
    }
    
    /**
     * Analyze listening patterns
     */
    private function getListeningPatterns(): array
    {
        $answers = $this->answers;
        $patterns = [];
        
        // Quick listeners (1 play, fast response)
        $quickListeners = $answers->filter(function ($answer) {
            return $answer->play_count <= 1 && $answer->time_spent_seconds <= 60;
        })->count();
        
        // Careful listeners (2-3 plays, moderate time)
        $carefulListeners = $answers->filter(function ($answer) {
            return $answer->play_count >= 2 && $answer->play_count <= 3;
        })->count();
        
        // Struggling listeners (4+ plays, long time)
        $strugglingQuestions = $answers->filter(function ($answer) {
            return $answer->play_count >= 4 || $answer->time_spent_seconds > 180;
        })->count();
        
        return [
            'quick_comprehension' => round(($quickListeners / $answers->count()) * 100, 1),
            'careful_listening' => round(($carefulListeners / $answers->count()) * 100, 1),
            'comprehension_challenges' => round(($strugglingQuestions / $answers->count()) * 100, 1)
        ];
    }
    
    /**
     * Get question type analysis
     */
    private function getQuestionTypeAnalysis(): array
    {
        return $this->answers->groupBy(function ($answer) {
            return $answer->question->question_type;
        })->map(function ($answers, $type) {
            $total = $answers->count();
            $correct = $answers->where('is_correct', true)->count();
            
            return [
                'question_type' => $type,
                'total_questions' => $total,
                'correct_answers' => $correct,
                'accuracy_percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
                'average_time' => round($answers->avg('time_spent_seconds'), 1),
                'average_plays' => round($answers->avg('play_count'), 1)
            ];
        })->values()->toArray();
    }
    
    /**
     * Format student answer for display
     */
    private function formatStudentAnswer($answer): string
    {
        if ($answer->selected_option_id && $answer->selectedOption) {
            return $answer->selectedOption->option_text;
        }
        
        if ($answer->text_answer) {
            return $answer->text_answer;
        }
        
        if ($answer->answer_data) {
            return 'Custom answer provided';
        }
        
        return 'No answer provided';
    }
    
    /**
     * Format correct answer for display
     */
    private function formatCorrectAnswer($question): string
    {
        $correctOption = $question->options->where('is_correct', true)->first();
        if ($correctOption) {
            return $correctOption->option_text;
        }
        
        if ($question->correct_answers) {
            return implode(', ', $question->correct_answers);
        }
        
        return 'See explanation';
    }
    
    /**
     * Assess question difficulty for student
     */
    private function assessQuestionDifficulty($answer): string
    {
        if ($answer->play_count <= 1 && $answer->time_spent_seconds <= 60) {
            return 'Easy';
        } elseif ($answer->play_count <= 3 && $answer->time_spent_seconds <= 120) {
            return 'Moderate';
        } elseif ($answer->play_count <= 5 && $answer->time_spent_seconds <= 240) {
            return 'Challenging';
        } else {
            return 'Very Difficult';
        }
    }
    
    /**
     * Get student strengths
     */
    private function getStrengths(): array
    {
        $strengths = [];
        
        if ($this->getAccuracyPercentage() >= 85) {
            $strengths[] = 'High overall accuracy';
        }
        
        if ($this->getListeningEfficiency() >= 80) {
            $strengths[] = 'Efficient audio comprehension';
        }
        
        if ($this->getTimeEfficiency() >= 80) {
            $strengths[] = 'Good time management';
        }
        
        // Analyze strong question types
        $questionAnalysis = $this->getQuestionTypeAnalysis();
        foreach ($questionAnalysis as $analysis) {
            if ($analysis['accuracy_percentage'] >= 90) {
                $strengths[] = "Excellent performance in {$analysis['question_type']} questions";
            }
        }
        
        return $strengths;
    }
    
    /**
     * Get areas for improvement
     */
    private function getAreasForImprovement(): array
    {
        $areas = [];
        
        if ($this->getAccuracyPercentage() < 70) {
            $areas[] = 'Overall comprehension accuracy';
        }
        
        if ($this->getListeningEfficiency() < 60) {
            $areas[] = 'Listen more carefully to reduce replays';
        }
        
        if ($this->getTimeEfficiency() < 60) {
            $areas[] = 'Time management during tests';
        }
        
        // Analyze weak question types
        $questionAnalysis = $this->getQuestionTypeAnalysis();
        foreach ($questionAnalysis as $analysis) {
            if ($analysis['accuracy_percentage'] < 60) {
                $areas[] = "Practice {$analysis['question_type']} question types";
            }
        }
        
        return $areas;
    }
    
    /**
     * Get study suggestions
     */
    private function getStudySuggestions(): array
    {
        $suggestions = [];
        
        $listeningPatterns = $this->getListeningPatterns();
        
        if ($listeningPatterns['comprehension_challenges'] > 30) {
            $suggestions[] = 'Practice active listening techniques';
            $suggestions[] = 'Focus on note-taking while listening';
        }
        
        if ($this->getAveragePlayCount() > 3) {
            $suggestions[] = 'Work on listening for main ideas first';
            $suggestions[] = 'Practice predicting content before listening';
        }
        
        if ($this->getTimeEfficiency() < 70) {
            $suggestions[] = 'Practice timed listening exercises';
            $suggestions[] = 'Develop strategies for quick decision making';
        }
        
        return $suggestions;
    }
    
    /**
     * Get next steps
     */
    private function getNextSteps(): array
    {
        $steps = [];
        
        if ($this->percentage >= 80) {
            $steps[] = 'Move to more advanced listening materials';
            $steps[] = 'Focus on accent variation and speaking speeds';
        } elseif ($this->percentage >= 60) {
            $steps[] = 'Review incorrect answers and explanations';
            $steps[] = 'Practice similar question types';
        } else {
            $steps[] = 'Review fundamental listening skills';
            $steps[] = 'Consider additional practice sessions';
        }
        
        return $steps;
    }
    
    /**
     * Get class average (placeholder - would need actual implementation)
     */
    private function getClassAverage(): ?float
    {
        // This would require querying all submissions for this test
        return null;
    }
    
    /**
     * Get percentile ranking (placeholder - would need actual implementation)
     */
    private function getPercentileRanking(): ?float
    {
        // This would require querying all submissions for this test
        return null;
    }
    
    /**
     * Get improvement from last attempt
     */
    private function getImprovementFromLastAttempt(): ?array
    {
        if ($this->attempt_number <= 1) {
            return null;
        }
        
        $previousSubmission = static::where('test_id', $this->test_id)
            ->where('student_id', $this->student_id)
            ->where('attempt_number', $this->attempt_number - 1)
            ->where('status', 'completed')
            ->first();
        
        if (!$previousSubmission) {
            return null;
        }
        
        return [
            'score_change' => $this->score - $previousSubmission->score,
            'percentage_change' => $this->percentage - $previousSubmission->percentage,
            'accuracy_change' => $this->getAccuracyPercentage() - 
                (($previousSubmission->correct_answers / $previousSubmission->total_questions) * 100)
        ];
    }
    
    /**
     * Calculate coefficient of variation for consistency analysis
     */
    private function calculateCoefficientsOfVariation(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        if ($mean == 0) return 0;
        
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        $stdDev = sqrt($variance);
        
        return $stdDev / $mean; // Coefficient of variation
    }
}