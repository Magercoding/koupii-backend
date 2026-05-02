<?php

namespace App\Services\V1\Class;

use App\Models\Classes;
use App\Models\ReadingSubmission;
use App\Models\ListeningSubmission;
use App\Models\WritingSubmission;
use App\Models\SpeakingSubmission;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassAnalyticsService
{
    public function getClassAnalytics(string $classId, string $type = 'reading', ?string $month = null)
    {
        $class = Classes::findOrFail($classId);
        
        // 1. Overall Class Stats
        $overallStats = $this->getOverallStats($classId);
        
        // 2. Skill Averages
        $skillAverages = $this->getSkillAverages($classId);
        
        // 3. Module Specific Details (Filtered by type)
        $moduleDetails = $this->getModuleDetails($classId, $type, $month);

        return [
            'class_info' => [
                'name' => $class->name,
                'total_students' => $class->students()->count(),
            ],
            'overall' => $overallStats,
            'skill_averages' => $skillAverages,
            'module_details' => $moduleDetails,
        ];
    }

    private function getOverallStats(string $classId)
    {
        // Simple average across all published tests in class
        $testIds = Test::where('class_id', $classId)->where('is_published', true)->pluck('id');
        
        if ($testIds->isEmpty()) {
            return ['average_score' => 0];
        }

        $readingAvg = ReadingSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0;
        $listeningAvg = ListeningSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0;
        $writingAvg = WritingSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0;
        $speakingAvg = SpeakingSubmission::whereIn('test_id', $testIds)->avg('average_score') ?? 0; // Speaking uses score 1-9 usually

        // Weighted average (simplified for now)
        $totalSum = $readingAvg + $listeningAvg + $writingAvg + ($speakingAvg * 11.11); // Normalize speaking to 100
        $count = ($readingAvg > 0 ? 1 : 0) + ($listeningAvg > 0 ? 1 : 0) + ($writingAvg > 0 ? 1 : 0) + ($speakingAvg > 0 ? 1 : 0);
        
        return [
            'average_score' => $count > 0 ? round($totalSum / $count, 2) : 0,
        ];
    }

    private function getSkillAverages(string $classId)
    {
        $testIds = Test::where('class_id', $classId)->pluck('id');

        return [
            ['skill' => 'Reading', 'score' => round(ReadingSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0, 2)],
            ['skill' => 'Listening', 'score' => round(ListeningSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0, 2)],
            ['skill' => 'Speaking', 'score' => round(SpeakingSubmission::whereIn('test_id', $testIds)->avg('average_score') * 11.11 ?? 0, 2)],
            ['skill' => 'Writing', 'score' => round(WritingSubmission::whereIn('test_id', $testIds)->avg('percentage') ?? 0, 2)],
        ];
    }

    private function getModuleDetails(string $classId, string $type, ?string $month = null)
    {
        $testIds = Test::where('class_id', $classId)
            ->where('type', $type)
            ->pluck('id');

        if ($testIds->isEmpty()) {
            return [
                'test_trends' => [],
                'weakest_areas' => [],
                'progress_history' => [],
            ];
        }

        // Get trends (average per test)
        $trends = Test::whereIn('id', $testIds)
            ->with(['readingSubmissions' => function($q) {
                $q->select('test_id', DB::raw('avg(percentage) as avg_score'))->groupBy('test_id');
            }])
            ->get()
            ->map(function(Test $test) use ($type) {
                $score = match($type) {
                    'reading' => $test->readingSubmissions()->avg('percentage') ?? 0,
                    'listening' => $test->listeningSubmissions()->avg('percentage') ?? 0,
                    'writing' => $test->writingSubmissions()->avg('percentage') ?? 0,
                    'speaking' => $test->speakingSubmissions()->avg('average_score') * 11.11 ?? 0,
                    default => 0
                };
                return [
                    'testName' => $test->title,
                    'average' => round($score, 2),
                    'label' => $test->title,
                ];
            });

        // Weakest Areas - This is more complex, for now returning placeholder or top level sections
        $weakest = [];
        if ($type === 'reading') {
            $weakest = [
                ['title' => 'Paragraph Completion', 'value' => 45],
                ['title' => 'Matching Heading', 'value' => 52],
                ['title' => 'Multiple Choice', 'value' => 68],
            ];
        }

        return [
            'test_trends' => $trends,
            'weakest_areas' => $weakest,
            'progress_history' => [], // Weekly progression
        ];
    }
}
