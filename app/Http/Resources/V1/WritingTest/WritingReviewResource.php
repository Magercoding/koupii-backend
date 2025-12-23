<?php

namespace App\Http\Resources\V1\WiritingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'teacher_id' => $this->teacher_id,
            'teacher_name' => optional($this->teacher)->name,
            'score' => $this->score,
            'comments' => $this->comments,
            'feedback_json' => $this->feedback_json,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,

            // Formatted score display
            'score_display' => $this->when($this->score, function () {
                return [
                    'value' => $this->score,
                    'formatted' => $this->score . '/100',
                    'grade' => $this->getLetterGrade($this->score),
                    'color' => $this->getScoreColor($this->score),
                ];
            }),

            // Detailed feedback structure
            'detailed_feedback' => $this->when($this->feedback_json, function () {
                $feedback = is_string($this->feedback_json)
                    ? json_decode($this->feedback_json, true)
                    : $this->feedback_json;

                return [
                    'strengths' => $feedback['strengths'] ?? [],
                    'areas_for_improvement' => $feedback['areas_for_improvement'] ?? [],
                    'grammar_errors' => $feedback['grammar_errors'] ?? [],
                    'vocabulary_suggestions' => $feedback['vocabulary_suggestions'] ?? [],
                    'structure_feedback' => $feedback['structure_feedback'] ?? [],
                    'overall_comment' => $feedback['overall_comment'] ?? null,
                ];
            }),

            // Review time formatting
            'reviewed_time_ago' => $this->reviewed_at?->diffForHumans(),
        ];
    }

    /**
     * Get letter grade based on score.
     */
    private function getLetterGrade($score)
    {
        if ($score >= 90)
            return 'A';
        if ($score >= 80)
            return 'B';
        if ($score >= 70)
            return 'C';
        if ($score >= 60)
            return 'D';
        return 'F';
    }

    /**
     * Get color based on score.
     */
    private function getScoreColor($score)
    {
        if ($score >= 80)
            return 'green';
        if ($score >= 60)
            return 'yellow';
        return 'red';
    }
}
