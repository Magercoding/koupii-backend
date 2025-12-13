<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'task_analytics' => [
                'task_id' => $this->resource['task_id'] ?? null,
                'total_submissions' => $this->resource['total_submissions'] ?? 0,
                'completed_submissions' => $this->resource['completed_submissions'] ?? 0,
                'completion_rate' => $this->resource['completion_rate'] ?? 0,
                'average_score' => $this->resource['average_score'] ?? 0,
                'median_score' => $this->resource['median_score'] ?? 0,
                'highest_score' => $this->resource['highest_score'] ?? 0,
                'lowest_score' => $this->resource['lowest_score'] ?? 0,
                'score_distribution' => $this->resource['score_distribution'] ?? [],
                'average_completion_time' => $this->resource['average_completion_time'] ?? 0,
                'median_completion_time' => $this->resource['median_completion_time'] ?? 0
            ],
            'question_analytics' => $this->resource['question_analytics'] ?? [],
            'audio_analytics' => [
                'total_plays' => $this->resource['audio_analytics']['total_plays'] ?? 0,
                'average_plays_per_submission' => $this->resource['audio_analytics']['average_plays_per_submission'] ?? 0,
                'most_played_segments' => $this->resource['audio_analytics']['most_played_segments'] ?? [],
                'audio_interaction_patterns' => $this->resource['audio_analytics']['audio_interaction_patterns'] ?? []
            ],
            'difficulty_analysis' => [
                'overall_difficulty' => $this->resource['difficulty_analysis']['overall_difficulty'] ?? 'medium',
                'difficulty_distribution' => $this->resource['difficulty_analysis']['difficulty_distribution'] ?? [],
                'suggested_adjustments' => $this->resource['difficulty_analysis']['suggested_adjustments'] ?? []
            ],
            'performance_insights' => [
                'strengths' => $this->resource['strengths'] ?? [],
                'areas_for_improvement' => $this->resource['areas_for_improvement'] ?? [],
                'recommendations' => $this->resource['recommendations'] ?? []
            ],
            'trends' => [
                'score_trend' => $this->resource['score_trend'] ?? 'stable',
                'improvement_rate' => $this->resource['improvement_rate'] ?? 0,
                'consistency_score' => $this->resource['consistency_score'] ?? 0
            ],
            'generated_at' => now()->toISOString(),
            'report_period' => [
                'start_date' => $this->resource['start_date'] ?? null,
                'end_date' => $this->resource['end_date'] ?? null,
                'total_days' => $this->resource['total_days'] ?? null
            ]
        ];
    }
}