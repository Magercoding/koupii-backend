<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningSubmissionResource extends JsonResource
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
            'total_questions' => $this->total_questions,
            'answered_questions' => $this->answered_questions,
            'correct_answers' => $this->correct_answers,
            'time_spent_minutes' => $this->time_spent_minutes,
            'audio_play_counts' => $this->audio_play_counts,
            'started_at' => $this->started_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'title' => $this->test->title,
                    'description' => $this->test->description,
                    'duration_minutes' => $this->test->duration_minutes,
                    'total_questions' => $this->test->testQuestions()->count(),
                    'total_points' => $this->test->testQuestions()->sum('points')
                ];
            }),
            
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'email' => $this->student->email
                ];
            }),
            
            'answers' => $this->whenLoaded('answers', function () {
                return ListeningQuestionAnswerResource::collection($this->answers);
            }),
            
            'audio_logs' => $this->whenLoaded('audioLogs', function () {
                return ListeningAudioLogResource::collection($this->audioLogs);
            }),
            
            // Progress indicators
            'progress' => [
                'completion_percentage' => $this->getCompletionPercentage(),
                'questions_answered' => $this->answered_questions ?? 0,
                'total_questions' => $this->total_questions ?? 0,
                'time_remaining_minutes' => $this->getTimeRemaining(),
                'audio_segments_played' => count($this->audio_play_counts ?? []),
                'total_audio_plays' => array_sum($this->audio_play_counts ?? [])
            ],
            
            // Performance metrics (only for completed submissions)
            'performance' => $this->when($this->status === 'completed', function () {
                return [
                    'accuracy_percentage' => $this->getAccuracyPercentage(),
                    'average_time_per_question' => $this->getAverageTimePerQuestion(),
                    'listening_efficiency' => $this->getListeningEfficiency(),
                    'strengths' => $this->getStrengths(),
                    'areas_for_improvement' => $this->getAreasForImprovement()
                ];
            }),
            
            // Metadata
            'metadata' => [
                'can_submit' => $this->canSubmit(),
                'can_edit' => $this->canEdit(),
                'is_expired' => $this->isExpired(),
                'submission_deadline' => $this->getSubmissionDeadline(),
                'total_audio_duration' => $this->getTotalAudioDuration()
            ]
        ];
    }
    
    /**
     * Calculate completion percentage
     */
    private function getCompletionPercentage(): float
    {
        $totalQuestions = $this->total_questions ?? 0;
        $answeredQuestions = $this->answered_questions ?? 0;
        
        return $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 1) : 0;
    }
    
    /**
     * Calculate time remaining in minutes
     */
    private function getTimeRemaining(): ?int
    {
        if (!$this->test || !$this->started_at) {
            return null;
        }
        
        $testDuration = $this->test->duration_minutes ?? 0;
        $elapsedMinutes = $this->started_at->diffInMinutes(now());
        
        return max(0, $testDuration - $elapsedMinutes);
    }
    
    /**
     * Calculate accuracy percentage
     */
    private function getAccuracyPercentage(): float
    {
        $correctAnswers = $this->correct_answers ?? 0;
        $totalQuestions = $this->total_questions ?? 0;
        
        return $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0;
    }
    
    /**
     * Calculate average time per question in seconds
     */
    private function getAverageTimePerQuestion(): float
    {
        $timeSpent = ($this->time_spent_minutes ?? 0) * 60;
        $totalQuestions = $this->total_questions ?? 0;
        
        return $totalQuestions > 0 ? round($timeSpent / $totalQuestions, 1) : 0;
    }
    
    /**
     * Calculate listening efficiency (lower audio plays = higher efficiency)
     */
    private function getListeningEfficiency(): float
    {
        $totalPlays = array_sum($this->audio_play_counts ?? []);
        $uniqueSegments = count($this->audio_play_counts ?? []);
        
        if ($uniqueSegments === 0) {
            return 0;
        }
        
        $averagePlaysPerSegment = $totalPlays / $uniqueSegments;
        // Efficiency score: 100% for 1 play per segment, decreasing with more plays
        return max(0, min(100, round((1 / $averagePlaysPerSegment) * 100, 1)));
    }
    
    /**
     * Identify student's strengths based on performance
     */
    private function getStrengths(): array
    {
        $strengths = [];
        
        if ($this->getListeningEfficiency() >= 80) {
            $strengths[] = 'Efficient audio comprehension';
        }
        
        if ($this->getAccuracyPercentage() >= 85) {
            $strengths[] = 'High accuracy rate';
        }
        
        if ($this->getAverageTimePerQuestion() <= 90) {
            $strengths[] = 'Quick response time';
        }
        
        return $strengths;
    }
    
    /**
     * Identify areas for improvement
     */
    private function getAreasForImprovement(): array
    {
        $areas = [];
        
        if ($this->getListeningEfficiency() < 60) {
            $areas[] = 'Focus on listening more carefully to reduce replays';
        }
        
        if ($this->getAccuracyPercentage() < 70) {
            $areas[] = 'Review listening comprehension strategies';
        }
        
        if ($this->getAverageTimePerQuestion() > 180) {
            $areas[] = 'Practice time management during listening tests';
        }
        
        return $areas;
    }
    
    /**
     * Check if submission can be submitted
     */
    private function canSubmit(): bool
    {
        return $this->status === 'in_progress' && !$this->isExpired();
    }
    
    /**
     * Check if submission can be edited
     */
    private function canEdit(): bool
    {
        return $this->status === 'in_progress' && !$this->isExpired();
    }
    
    /**
     * Check if submission is expired
     */
    private function isExpired(): bool
    {
        if (!$this->test || !$this->started_at) {
            return false;
        }
        
        $testDuration = $this->test->duration_minutes ?? 0;
        $deadline = $this->started_at->addMinutes($testDuration);
        
        return now()->gt($deadline);
    }
    
    /**
     * Get submission deadline
     */
    private function getSubmissionDeadline(): ?string
    {
        if (!$this->test || !$this->started_at) {
            return null;
        }
        
        $testDuration = $this->test->duration_minutes ?? 0;
        return $this->started_at->addMinutes($testDuration)->toISOString();
    }
    
    /**
     * Get total audio duration
     */
    private function getTotalAudioDuration(): float
    {
        if (!$this->test) {
            return 0;
        }
        
        return $this->test->passages()
            ->with('audioSegments')
            ->get()
            ->sum(function ($passage) {
                return $passage->audioSegments->sum('duration');
            });
    }
}