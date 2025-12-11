<?php

namespace App\Services\V1\ReadingTest;

use App\Models\ReadingSubmission;
use App\Models\StudentVocabularyDiscovery;
use App\Models\StudentVocabularyBank;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class ReadingVocabularyService
{
    /**
     * Get discovered vocabularies from a test submission
     */
    public function getDiscoveredVocabularies(ReadingSubmission $submission): Collection
    {
        return StudentVocabularyDiscovery::with('vocabulary')
            ->where('student_id', $submission->student_id)
            ->where('test_id', $submission->test_id)
            ->get();
    }

    /**
     * Save vocabulary to student's bank
     */
    public function saveVocabularyToBank(string $studentId, string $vocabularyId, string $testId): StudentVocabularyBank
    {
        // Check if discovery exists
        $discovery = StudentVocabularyDiscovery::where('student_id', $studentId)
            ->where('vocabulary_id', $vocabularyId)
            ->where('test_id', $testId)
            ->first();

        if (!$discovery) {
            // Create discovery if it doesn't exist
            $discovery = StudentVocabularyDiscovery::create([
                'student_id' => $studentId,
                'vocabulary_id' => $vocabularyId,
                'test_id' => $testId,
                'discovered_at' => now(),
                'is_saved' => false,
            ]);
        }

        return $discovery->saveToBank();
    }

    /**
     * Get student's vocabulary bank with filtering
     */
    public function getStudentVocabularyBank(string $studentId, array $filters = []): LengthAwarePaginator
    {
        return StudentVocabularyBank::with(['vocabulary', 'discoveredFromTest:id,title'])
            ->where('student_id', $studentId)
            ->when($filters['mastery_level'] ?? null, function ($query, $level) {
                match ($level) {
                    'mastered' => $query->where('is_mastered', true),
                    'advanced' => $query->where('is_mastered', false)->where('practice_count', '>=', 3),
                    'beginner' => $query->where('is_mastered', false)->where('practice_count', '>=', 1)->where('practice_count', '<', 3),
                    'new' => $query->where('practice_count', 0),
                    default => $query
                };
            })
            ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
                $query->whereHas('vocabulary', fn($q) => $q->where('category_id', $categoryId));
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('vocabulary', function ($q) use ($search) {
                    $q->where('word', 'like', "%{$search}%")
                      ->orWhere('translation', 'like', "%{$search}%");
                });
            })
            ->when($filters['recently_practiced'] ?? null, function ($query, $days) {
                $query->where('last_practiced_at', '>=', now()->subDays($days));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Record vocabulary practice session
     */
    public function practiceVocabulary(string $studentId, string $vocabularyId): void
    {
        $bankEntry = StudentVocabularyBank::where('student_id', $studentId)
            ->where('vocabulary_id', $vocabularyId)
            ->first();

        if (!$bankEntry) {
            throw new Exception('Vocabulary not found in your bank');
        }

        $bankEntry->practice();
    }

    /**
     * Get vocabulary statistics for student
     */
    public function getVocabularyStatistics(string $studentId): array
    {
        $totalVocabularies = StudentVocabularyBank::where('student_id', $studentId)->count();
        $masteredCount = StudentVocabularyBank::where('student_id', $studentId)->mastered()->count();
        $recentlyPracticedCount = StudentVocabularyBank::where('student_id', $studentId)
            ->recentlyPracticed(7)->count();

        $totalDiscoveries = StudentVocabularyDiscovery::where('student_id', $studentId)->count();
        $savedDiscoveries = StudentVocabularyDiscovery::where('student_id', $studentId)
            ->where('is_saved', true)->count();

        return [
            'total_vocabularies' => $totalVocabularies,
            'mastered_count' => $masteredCount,
            'mastery_percentage' => $totalVocabularies > 0 ? round(($masteredCount / $totalVocabularies) * 100, 1) : 0,
            'recently_practiced' => $recentlyPracticedCount,
            'total_discoveries' => $totalDiscoveries,
            'saved_discoveries' => $savedDiscoveries,
            'save_rate' => $totalDiscoveries > 0 ? round(($savedDiscoveries / $totalDiscoveries) * 100, 1) : 0,
            'mastery_levels' => [
                'mastered' => $masteredCount,
                'advanced' => StudentVocabularyBank::where('student_id', $studentId)
                    ->notMastered()
                    ->where('practice_count', '>=', 3)
                    ->count(),
                'beginner' => StudentVocabularyBank::where('student_id', $studentId)
                    ->notMastered()
                    ->where('practice_count', '>=', 1)
                    ->where('practice_count', '<', 3)
                    ->count(),
                'new' => StudentVocabularyBank::where('student_id', $studentId)
                    ->where('practice_count', 0)
                    ->count(),
            ]
        ];
    }

    /**
     * Get vocabulary recommendations for student
     */
    public function getVocabularyRecommendations(string $studentId, int $limit = 10): Collection
    {
        // Get vocabularies that need practice (not mastered, low practice count)
        return StudentVocabularyBank::with('vocabulary')
            ->where('student_id', $studentId)
            ->notMastered()
            ->orderBy('practice_count', 'asc')
            ->orderBy('last_practiced_at', 'asc')
            ->limit($limit)
            ->get();
    }
}