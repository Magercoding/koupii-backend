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
        // Get the task title and metadata
        $taskTitle = $this->resource['task_title'] ?? 'Untitled Task';
        $totalSubmissions = $this->resource['total_submissions'] ?? 0;
        
        // Helper to map question types to best/weak performance
        $questionTypeStats = $this->resource['question_type_performance'] ?? [];
        if (empty($questionTypeStats) && isset($this->resource['question_analytics'])) {
            // Basic mapping if type stats aren't pre-calculated
            $typeMap = [];
            foreach ($this->resource['question_analytics'] as $q) {
                $type = $q['question_type'] ?? 'General';
                if (!isset($typeMap[$type])) {
                    $typeMap[$type] = ['accuracy' => 0, 'count' => 0];
                }
                $typeMap[$type]['accuracy'] += $q['accuracy'];
                $typeMap[$type]['count']++;
            }
            foreach ($typeMap as $type => $data) {
                $questionTypeStats[] = [
                    'title' => ucwords(str_replace('_', ' ', $type)),
                    'accurate' => round($data['accuracy'] / $data['count'], 0)
                ];
            }
        } else {
            // Map existing question type performance to accurate
            $questionTypeStats = array_map(function($item) {
                return [
                    'title' => $item['name'] ?? ucwords(str_replace('_', ' ', $item['type'] ?? 'General')),
                    'accurate' => (int)($item['accuracy'] ?? 0)
                ];
            }, $questionTypeStats);
        }

        usort($questionTypeStats, fn($a, $b) => $b['accurate'] <=> $a['accurate']);
        $bestPerformance = array_slice($questionTypeStats, 0, 3);
        $weakPerformance = array_reverse(array_slice(array_reverse($questionTypeStats), 0, 3));

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
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => count($this->resource['leaderboard'] ?? [])
            ],
            
            // Keep original for back-compat if needed
            'original_analytics' => [
                'task_analytics' => [
                    'task_id' => $this->resource['task_id'] ?? null,
                    'total_submissions' => $totalSubmissions,
                    'completed_submissions' => $this->resource['completed_submissions'] ?? 0,
                    'completion_rate' => $this->resource['completion_rate'] ?? 0,
                    'average_score' => $this->resource['average_score'] ?? 0,
                    'highest_score' => $this->resource['highest_score'] ?? 0,
                    'lowest_score' => $this->resource['lowest_score'] ?? 0,
                ],
                'question_analytics' => $this->resource['question_analytics'] ?? [],
                'audio_analytics' => $this->resource['audio_analytics'] ?? []
            ],
            'generated_at' => now()->toISOString()
        ];
    }
}