<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingFeedbackResource extends JsonResource
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
            'feedback_type' => $this->feedback_type,
            'feedback_type_label' => $this->getFeedbackTypeLabel(),
            'score' => $this->score,
            'max_score' => $this->max_score,
            'percentage_score' => $this->percentage_score,
            'grade_level' => $this->grade_level,
            'comments' => $this->comments,
            'detailed_feedback' => $this->detailed_feedback,
            'suggestions' => $this->suggestions,
            'is_automated' => $this->graded_by === null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'submission' => $this->whenLoaded('submission', function () {
                return [
                    'id' => $this->submission->id,
                    'content_preview' => substr(strip_tags($this->submission->content), 0, 150) . '...',
                    'word_count' => $this->submission->word_count,
                    'status' => $this->submission->status,
                ];
            }),

            'question' => $this->whenLoaded('question', function () {
                return [
                    'id' => $this->question->id,
                    'question_number' => $this->question->question_number,
                    'question_text' => $this->question->question_text,
                    'question_type' => $this->question->question_type,
                    'points' => $this->question->points,
                ];
            }),

            'grader' => $this->whenLoaded('grader', function () {
                return [
                    'id' => $this->grader->id,
                    'name' => $this->grader->name,
                    'email' => $this->grader->email,
                    'role' => $this->grader->role,
                ];
            }),

            // Feedback analysis
            'feedback_analysis' => $this->when($this->detailed_feedback, function () {
                return [
                    'strengths' => $this->getStrengths(),
                    'areas_for_improvement' => $this->getAreasForImprovement(),
                    'key_metrics' => $this->getKeyMetrics(),
                ];
            }),
        ];
    }

    /**
     * Get human-readable feedback type label
     */
    private function getFeedbackTypeLabel(): string
    {
        return match($this->feedback_type) {
            'overall' => 'Overall Assessment',
            'grammar' => 'Grammar & Syntax',
            'content' => 'Content Quality',
            'structure' => 'Organization & Structure',
            'vocabulary' => 'Vocabulary & Language Use',
            'coherence' => 'Coherence & Cohesion',
            default => ucfirst(str_replace('_', ' ', $this->feedback_type)),
        };
    }

    /**
     * Extract strengths from detailed feedback
     */
    private function getStrengths(): array
    {
        if (!$this->detailed_feedback || !is_array($this->detailed_feedback)) {
            return [];
        }

        // Extract positive aspects based on feedback type
        $strengths = [];

        switch ($this->feedback_type) {
            case 'grammar':
                if (($this->detailed_feedback['grammar_errors'] ?? 0) <= 2) {
                    $strengths[] = 'Good grammar accuracy';
                }
                break;
            
            case 'vocabulary':
                if (($this->detailed_feedback['advanced_words'] ?? 0) > 10) {
                    $strengths[] = 'Rich vocabulary usage';
                }
                break;
            
            case 'structure':
                if (($this->detailed_feedback['transition_score'] ?? 0) > 7) {
                    $strengths[] = 'Clear paragraph transitions';
                }
                break;
        }

        return $strengths;
    }

    /**
     * Extract areas for improvement from detailed feedback
     */
    private function getAreasForImprovement(): array
    {
        if (!$this->suggestions || !is_array($this->suggestions)) {
            return [];
        }

        return array_slice($this->suggestions, 0, 3); // Top 3 suggestions
    }

    /**
     * Extract key metrics from detailed feedback
     */
    private function getKeyMetrics(): array
    {
        if (!$this->detailed_feedback || !is_array($this->detailed_feedback)) {
            return [];
        }

        $metrics = [];

        foreach ($this->detailed_feedback as $key => $value) {
            if (is_numeric($value)) {
                $metrics[ucfirst(str_replace('_', ' ', $key))] = $value;
            }
        }

        return $metrics;
    }
}