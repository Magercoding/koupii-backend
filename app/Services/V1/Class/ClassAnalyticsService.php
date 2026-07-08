<?php

namespace App\Services\V1\Class;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\ListeningSubmission;
use App\Models\ListeningTask;
use App\Models\ReadingSubmission;
use App\Models\ReadingTask;
use App\Models\SpeakingReview;
use App\Models\SpeakingSection;
use App\Models\SpeakingSubmission;
use App\Models\Test;
use App\Models\WritingSubmission;
use App\Models\WritingTask;
use Illuminate\Support\Facades\DB;

class ClassAnalyticsService
{
    public function getClassAnalytics(string $classId, string $type = 'reading', ?string $month = null): array
    {
        $class = Classes::findOrFail($classId);

        return [
            'class_info' => [
                'name'           => $class->name,
                'total_students' => $class->students()->count(),
            ],
            'overall'        => $this->getOverallStats($classId),
            'skill_averages' => $this->getSkillAverages($classId),
            'module_details' => $this->getModuleDetails($classId, $type, $month),
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /** All test IDs assigned to this class (via assignments table) */
    private function classTestIds(string $classId)
    {
        return Assignment::where('class_id', $classId)
            ->whereNotNull('test_id')
            ->pluck('test_id')
            ->unique();
    }

    /**
     * Task IDs for a given task_type assigned to this class.
     * Checks both `task_type` (standalone task assignments) and
     * `type` (test-derived assignments where task_type may be null).
     */
    private function classTaskIds(string $classId, string $taskType)
    {
        return Assignment::where('class_id', $classId)
            ->whereNotNull('task_id')
            ->where(function ($q) use ($taskType) {
                $q->where('task_type', $taskType)
                  ->orWhere('type', $taskType);
            })
            ->pluck('task_id')
            ->unique();
    }

    private function allClassWritingTaskIds(string $classId)
    {
        $testIds = $this->classTestIds($classId);

        $fromTests = WritingTask::whereIn('test_id',
            Test::whereIn('id', $testIds)->where('type', 'writing')->pluck('id')
        )->pluck('id');

        $fromAssignments = $this->classTaskIds($classId, 'writing_task');

        $fromClassId = WritingTask::where('class_id', $classId)->pluck('id');

        return $fromTests->merge($fromAssignments)->merge($fromClassId)->unique();
    }

    private function writingAvg($writingTaskIds): float
    {
        if ($writingTaskIds->isEmpty()) {
            return 0;
        }

        $avg = DB::table('writing_reviews')
            ->join('writing_submissions', 'writing_reviews.submission_id', '=', 'writing_submissions.id')
            ->whereIn('writing_submissions.writing_task_id', $writingTaskIds)
            ->whereNotNull('writing_reviews.score')
            ->avg('writing_reviews.score');

        return (float) ($avg ?? 0);
    }

    /** Average speaking score from speaking_reviews (column: total_score) */
    private function speakingAvg($speakingTaskIds): float
    {
        if ($speakingTaskIds->isEmpty()) {
            return 0;
        }
        return (float) (SpeakingReview::whereHas('submission', function ($q) use ($speakingTaskIds) {
            $q->whereIn('speaking_task_id', $speakingTaskIds);
        })->whereNotNull('total_score')->avg('total_score') ?? 0);
    }

    // ─── Overall Stats ──────────────────────────────────────────────────────────

    private function getOverallStats(string $classId): array
    {
        $testIds = $this->classTestIds($classId);

        // Reading
        $readingTestIds  = Test::whereIn('id', $testIds)->where('type', 'reading')->pluck('id');
        $readingTaskIds  = $this->classTaskIds($classId, 'reading_task');
        $readingAvg = ReadingSubmission::where(function ($q) use ($readingTestIds, $readingTaskIds) {
            $q->whereIn('test_id', $readingTestIds)
              ->orWhereIn('reading_task_id', $readingTaskIds);
        })->whereNotNull('percentage')->avg('percentage') ?? 0;

        // Listening
        $listeningTestTaskIds = ListeningTask::whereIn('test_id',
            Test::whereIn('id', $testIds)->where('type', 'listening')->pluck('id')
        )->pluck('id');
        $listeningTaskIds = $this->classTaskIds($classId, 'listening_task');
        $allListeningIds  = $listeningTestTaskIds->merge($listeningTaskIds)->unique();
        $listeningAvg = ListeningSubmission::whereIn('listening_task_id', $allListeningIds)
            ->whereNotNull('percentage')->avg('percentage') ?? 0;

        // Writing — use unified helper
        $allWritingIds = $this->allClassWritingTaskIds($classId);
        $writingAvg    = $this->writingAvg($allWritingIds);

        // Speaking
        $speakingTestSectionIds = SpeakingSection::whereIn('test_id',
            Test::whereIn('id', $testIds)->where('type', 'speaking')->pluck('id')
        )->pluck('id');
        $speakingTaskIds = $this->classTaskIds($classId, 'speaking_task');
        $allSpeakingIds  = $speakingTestSectionIds->merge($speakingTaskIds)->unique();
        $speakingAvg = $this->speakingAvg($allSpeakingIds);

        $scores = collect([$readingAvg, $listeningAvg, $writingAvg, $speakingAvg])->filter(fn($v) => $v > 0);

        return ['average_score' => $scores->isNotEmpty() ? round($scores->avg(), 2) : 0];
    }

    // ─── Skill Averages ─────────────────────────────────────────────────────────

    private function getSkillAverages(string $classId): array
    {
        $testIds = $this->classTestIds($classId);

        // Reading
        $readingTestIds = Test::whereIn('id', $testIds)->where('type', 'reading')->pluck('id');
        $readingTaskIds = $this->classTaskIds($classId, 'reading_task');
        $readingScore = ReadingSubmission::where(function ($q) use ($readingTestIds, $readingTaskIds) {
            $q->whereIn('test_id', $readingTestIds)
              ->orWhereIn('reading_task_id', $readingTaskIds);
        })->whereNotNull('percentage')->avg('percentage') ?? 0;

        // Listening
        $listeningTestTaskIds = ListeningTask::whereIn('test_id',
            Test::whereIn('id', $testIds)->where('type', 'listening')->pluck('id')
        )->pluck('id');
        $listeningTaskIds = $this->classTaskIds($classId, 'listening_task');
        $allListeningIds  = $listeningTestTaskIds->merge($listeningTaskIds)->unique();
        $listeningScore = ListeningSubmission::whereIn('listening_task_id', $allListeningIds)
            ->whereNotNull('percentage')->avg('percentage') ?? 0;

        // Speaking
        $speakingTestSectionIds = SpeakingSection::whereIn('test_id',
            Test::whereIn('id', $testIds)->where('type', 'speaking')->pluck('id')
        )->pluck('id');
        $speakingTaskIds = $this->classTaskIds($classId, 'speaking_task');
        $allSpeakingIds  = $speakingTestSectionIds->merge($speakingTaskIds)->unique();
        $speakingScore = $this->speakingAvg($allSpeakingIds);

        // Writing — use unified helper that covers tests + assignments + direct class_id
        $allWritingIds = $this->allClassWritingTaskIds($classId);
        $writingScore  = $this->writingAvg($allWritingIds);

        return [
            ['skill' => 'Reading',   'score' => round((float) $readingScore, 2)],
            ['skill' => 'Listening', 'score' => round((float) $listeningScore, 2)],
            ['skill' => 'Speaking',  'score' => round($speakingScore, 2)],
            ['skill' => 'Writing',   'score' => round($writingScore, 2)],
        ];
    }

    // ─── Module Details ─────────────────────────────────────────────────────────

    private function getModuleDetails(string $classId, string $type, ?string $month): array
    {
        $testIds     = $this->classTestIds($classId);
        $typeTestIds = Test::whereIn('id', $testIds)->where('type', $type)->pluck('id');
        $taskIds     = $this->classTaskIds($classId, $type . '_task');

        if ($typeTestIds->isEmpty() && $taskIds->isEmpty()) {
            return ['test_trends' => [], 'weakest_areas' => [], 'progress_history' => []];
        }

        [$trends, $weakest] = match ($type) {
            'reading'  => [$this->getReadingTrends($typeTestIds, $taskIds),  $this->getReadingWeakestAreas($typeTestIds, $taskIds)],
            'listening'=> [$this->getListeningTrends($typeTestIds, $taskIds),$this->getListeningWeakestAreas($typeTestIds, $taskIds)],
            'writing'  => [$this->getWritingTrends($typeTestIds, $taskIds),  $this->getWritingRevisionInsights($typeTestIds, $taskIds)],
            'speaking' => [$this->getSpeakingTrends($typeTestIds, $taskIds), $this->getSpeakingRevisionInsights($typeTestIds, $taskIds)],
            default    => [collect(), []],
        };

        return [
            'test_trends'      => $trends->values()->toArray(),
            'weakest_areas'    => $weakest,
            'progress_history' => [],
        ];
    }

    // ─── Reading ────────────────────────────────────────────────────────────────

    private function getReadingTrends($testIds, $taskIds)
    {
        $fromTests = Test::whereIn('id', $testIds)->get()->map(function (Test $test) {
            $avg = ReadingSubmission::where('test_id', $test->id)
                ->whereNotNull('percentage')->avg('percentage') ?? 0;
            return ['label' => $test->title, 'average' => round((float) $avg, 2)];
        });

        $fromTasks = ReadingTask::whereIn('id', $taskIds)->get()->map(function (ReadingTask $task) {
            $avg = ReadingSubmission::where('reading_task_id', $task->id)
                ->whereNotNull('percentage')->avg('percentage') ?? 0;
            return ['label' => $task->title, 'average' => round((float) $avg, 2)];
        });

        return collect($fromTests)->concat($fromTasks);
    }

    private function getReadingWeakestAreas($testIds, $taskIds): array
    {
        $rows = ReadingSubmission::where(function ($q) use ($testIds, $taskIds) {
            $q->whereIn('test_id', $testIds)
              ->orWhereIn('reading_task_id', $taskIds);
        })
        ->whereNotNull('percentage')
        ->whereNotNull('reading_task_id')
        ->select('reading_task_id', DB::raw('AVG(percentage) as avg_pct'))
        ->groupBy('reading_task_id')
        ->orderBy('avg_pct')
        ->limit(5)
        ->get();

        return $rows->map(function ($row) {
            $task = ReadingTask::find($row->reading_task_id);
            return [
                'title' => $task?->title ?? 'Unknown Task',
                'value' => round((float) $row->avg_pct, 1),
            ];
        })->toArray();
    }

    // ─── Listening ──────────────────────────────────────────────────────────────

    private function getListeningTrends($testIds, $taskIds)
    {
        $fromTests = Test::whereIn('id', $testIds)->get()->map(function (Test $test) {
            $lTaskIds = ListeningTask::where('test_id', $test->id)->pluck('id');
            $avg = ListeningSubmission::whereIn('listening_task_id', $lTaskIds)
                ->whereNotNull('percentage')->avg('percentage') ?? 0;
            return ['label' => $test->title, 'average' => round((float) $avg, 2)];
        });

        $fromTasks = ListeningTask::whereIn('id', $taskIds)->get()->map(function (ListeningTask $task) {
            $avg = ListeningSubmission::where('listening_task_id', $task->id)
                ->whereNotNull('percentage')->avg('percentage') ?? 0;
            return ['label' => $task->title, 'average' => round((float) $avg, 2)];
        });

        return collect($fromTests)->concat($fromTasks);
    }

    private function getListeningWeakestAreas($testIds, $taskIds): array
    {
        $allListeningTaskIds = ListeningTask::whereIn('test_id', $testIds)->pluck('id')
            ->merge($taskIds)->unique();

        if ($allListeningTaskIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('listening_question_answers as lqa')
            ->join('listening_submissions as ls', 'lqa.submission_id', '=', 'ls.id')
            ->join('listening_questions as lq', 'lqa.question_id', '=', 'lq.id')
            ->whereIn('ls.listening_task_id', $allListeningTaskIds)
            ->whereNotNull('lq.question_type')
            ->select(
                'lq.question_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN lqa.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('lq.question_type')
            ->orderByRaw('(SUM(CASE WHEN lqa.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) ASC')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) {
            $accuracy = $row->total > 0 ? round(($row->correct / $row->total) * 100, 1) : 0;
            return [
                'title' => ucwords(str_replace('_', ' ', $row->question_type)),
                'value' => $accuracy,
            ];
        })->toArray();
    }

    // ─── Writing ────────────────────────────────────────────────────────────────

    private function getWritingTrends($testIds, $taskIds)
    {
        $fromTests = Test::whereIn('id', $testIds)->get()->map(function (Test $test) {
            $wTaskIds = WritingTask::where('test_id', $test->id)->pluck('id');
            $avg = $this->writingAvg($wTaskIds);
            return ['label' => $test->title, 'average' => round($avg, 2)];
        });

        $fromTasks = WritingTask::whereIn('id', $taskIds)->get()->map(function (WritingTask $task) {
            $avg = $this->writingAvg(collect([$task->id]));
            return ['label' => $task->title, 'average' => round($avg, 2)];
        });

        return collect($fromTests)->concat($fromTasks);
    }

    private function getWritingRevisionInsights($testIds, $taskIds): array
    {
        // Resolve writing task IDs from tests
        $testWritingTaskIds = WritingTask::whereIn('test_id', $testIds)->pluck('id');

        $allTaskIds = $testWritingTaskIds->merge($taskIds)->unique();

        if ($allTaskIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('writing_submissions')
            ->whereIn('writing_task_id', $allTaskIds)
            ->select(
                'writing_task_id',
                DB::raw('COUNT(*) as total_revisions'),
                DB::raw('AVG(attempt_number) as avg_revisions')
            )
            ->groupBy('writing_task_id')
            ->orderByDesc('total_revisions')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) {
            $task = WritingTask::find($row->writing_task_id);
            return [
                'title'          => $task?->title ?? 'Unknown Task',
                'totalRevisions' => (int) $row->total_revisions,
                'avgRevisions'   => round((float) $row->avg_revisions, 1),
            ];
        })->toArray();
    }

    // ─── Speaking ───────────────────────────────────────────────────────────────

    private function getSpeakingTrends($testIds, $taskIds)
    {
        $fromTests = Test::whereIn('id', $testIds)->get()->map(function (Test $test) {
            $sectionIds = SpeakingSection::where('test_id', $test->id)->pluck('id');
            $avg = $this->speakingAvg($sectionIds);
            return ['label' => $test->title, 'average' => round($avg, 2)];
        });

        $fromTasks = SpeakingSection::whereIn('id', $taskIds)->get()->map(function (SpeakingSection $section) {
            $avg = $this->speakingAvg(collect([$section->id]));
            return ['label' => $section->title ?? ('Section ' . $section->id), 'average' => round($avg, 2)];
        });

        return collect($fromTests)->concat($fromTasks);
    }

    private function getSpeakingRevisionInsights($testIds, $taskIds): array
    {
        $sectionIds = SpeakingSection::whereIn('test_id', $testIds)->pluck('id')
            ->merge($taskIds)->unique();

        if ($sectionIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('speaking_submissions')
            ->whereIn('speaking_task_id', $sectionIds)
            ->select(
                'speaking_task_id',
                DB::raw('COUNT(*) as total_revisions'),
                DB::raw('AVG(attempt_number) as avg_revisions')
            )
            ->groupBy('speaking_task_id')
            ->orderByDesc('total_revisions')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) {
            $section = SpeakingSection::find($row->speaking_task_id);
            return [
                'title'          => $section?->title ?? 'Unknown Section',
                'totalRevisions' => (int) $row->total_revisions,
                'avgRevisions'   => round((float) $row->avg_revisions, 1),
            ];
        })->toArray();
    }
}
