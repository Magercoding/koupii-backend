<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningQuestionAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'question_id' => $this->question_id,
            'selected_option_id' => $this->selected_option_id,
            'text_answer' => $this->text_answer,
            'answer_data' => $this->answer_data,
            'is_correct' => $this->is_correct,
            'points_earned' => $this->points_earned,
            'time_spent_seconds' => $this->time_spent_seconds,
            'play_count' => $this->play_count,
            'answer_explanation' => $this->answer_explanation,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Computed fields
            'time_spent_formatted' => $this->getFormattedTimeSpent(),
            'is_answered' => $this->isAnswered(),
            'confidence_score' => $this->getConfidenceScore(),
            'answer_summary' => $this->getAnswerSummary(),
            
            // Include question information when loaded
            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_text' => $this->question->question_text,
                    'question_type' => $this->question->question_type,
                    'question_order' => $this->question->question_order,
                    'points' => $this->question->points ?? 1
                ];
            }),

            // Include selected option details when loaded
            'selected_option' => $this->whenLoaded('selectedOption', function () {
                return [
                    'id' => $this->selectedOption->id,
                    'option_text' => $this->selectedOption->option_text,
                    'is_correct' => $this->selectedOption->is_correct,
                    'explanation' => $this->selectedOption->explanation
                ];
            }),
            
            // Include performance insights
            'performance' => [
                'difficulty_assessment' => $this->getDifficultyAssessment(),
                'listening_pattern' => $this->getListeningPattern(),
                'response_quality' => $this->getResponseQuality()
            ]
        ];
    }
    
    /**
     * Get formatted time spent
     */
    private function getFormattedTimeSpent(): ?string
    {
        if (!$this->time_spent_seconds) {
            return null;
        }
        
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    /**
     * Check if the question is answered
     */
    private function isAnswered(): bool
    {
        return !empty($this->selected_option_id) || 
               !empty($this->text_answer) || 
               !empty($this->answer_data);
    }
    
    /**
     * Get confidence score based on play count and time spent
     */
    private function getConfidenceScore(): float
    {
        // Base score starts at 100
        $score = 100;
        
        // Reduce score for excessive audio plays
        if ($this->play_count > 3) {
            $score -= ($this->play_count - 3) * 10;
        }
        
        // Reduce score for excessive time spent
        if ($this->time_spent_seconds > 180) {
            $score -= (($this->time_spent_seconds - 180) / 60) * 5;
        }
        
        return max(0, min(100, round($score, 1)));
    }
    
    /**
     * Get answer summary for display
     */
    private function getAnswerSummary(): string
    {
        if (!$this->isAnswered()) {
            return 'Not answered';
        }
        
        if ($this->selected_option_id && $this->selectedOption) {
            return 'Selected: ' . substr($this->selectedOption->option_text, 0, 50);
        }
        
        if ($this->text_answer) {
            return 'Text answer: ' . substr($this->text_answer, 0, 50);
        }
        
        if ($this->answer_data) {
            return 'Custom answer provided';
        }
        
        return 'Answered';
    }
    
    /**
     * Assess difficulty based on performance metrics
     */
    private function getDifficultyAssessment(): string
    {
        if ($this->play_count <= 1 && $this->time_spent_seconds <= 60) {
            return 'Easy';
        }
        
        if ($this->play_count <= 3 && $this->time_spent_seconds <= 120) {
            return 'Moderate';
        }
        
        if ($this->play_count <= 5 && $this->time_spent_seconds <= 240) {
            return 'Challenging';
        }
        
        return 'Difficult';
    }
    
    /**
     * Analyze listening pattern
     */
    private function getListeningPattern(): string
    {
        if ($this->play_count === 1) {
            return 'Single listen';
        }
        
        if ($this->play_count <= 2) {
            return 'Careful listener';
        }
        
        if ($this->play_count <= 4) {
            return 'Multiple replays';
        }
        
        return 'Extensive replaying';
    }
    
    /**
     * Assess response quality
     */
    private function getResponseQuality(): ?string
    {
        if ($this->is_correct === null) {
            return null;
        }
        
        if ($this->is_correct) {
            if ($this->play_count <= 2 && $this->time_spent_seconds <= 90) {
                return 'Excellent';
            }
            return 'Good';
        }
        
        if ($this->play_count > 3) {
            return 'Needs improvement - struggled with comprehension';
        }
        
        return 'Needs improvement';
    }
}
