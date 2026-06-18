<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingAnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $taskTitle = $this->resource['task_title'] ?? 'Untitled Task';
        $totalSubmissions = $this->resource['total_submissions'] ?? 0;

        $questionTypeStats = array_map(function ($item) {
            return [
                'title' => $item['name'] ?? ucwords(str_replace('_', ' ', $item['type'] ?? 'General')),
                'accurate' => (int) ($item['accuracy'] ?? 0),
            ];
        }, $this->resource['question_type_performance'] ?? []);

        usort($questionTypeStats, fn ($a, $b) => $b['accurate'] <=> $a['accurate']);
        $bestPerformance = array_slice($questionTypeStats, 0, 3);
        $weakPerformance = array_reverse(array_slice(array_reverse($questionTypeStats), 0, 3));

        $meta = $this->resource['leaderboard_meta'] ?? [
            'current_page' => 1,
            'last_page' => 1,
            'total' => count($this->resource['leaderboard'] ?? []),
        ];

        return [
            'test_name' => $taskTitle,
            'attempts' => $totalSubmissions,
            'class' => $this->resource['class_name'] ?? 'General Class',
            'created_at' => $this->resource['created_at'] ?? now()->format('d M Y'),
            'stats' => [
                'highest_score' => $this->resource['highest_score'] ?? 0,
                'average_score' => $this->resource['average_score'] ?? 0,
                'lowest_score' => $this->resource['lowest_score'] ?? 0,
            ],
            'best_performance' => $bestPerformance,
            'weak_performance' => $weakPerformance,
            'leaderboard' => $this->resource['leaderboard'] ?? [],
            'meta' => $meta,
        ];
    }
}
