<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningVocabularyDiscovery;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ListeningVocabularyService
{
    /**
     * Discover a new vocabulary word from listening content
     */
    public function discoverWord(User $student, array $wordData): ListeningVocabularyDiscovery
    {
        // Check if word already exists for this student and test
        $existingWord = ListeningVocabularyDiscovery::where('student_id', $student->id)
            ->where('test_id', $wordData['test_id'])
            ->where('word', strtolower($wordData['word']))
            ->first();

        if ($existingWord) {
            // If word exists, mark as reviewed and return
            $existingWord->markAsReviewed();
            return $existingWord;
        }

        return ListeningVocabularyDiscovery::create([
            'test_id' => $wordData['test_id'],
            'student_id' => $student->id,
            'word' => strtolower($wordData['word']),
            'definition' => $wordData['definition'],
            'context_sentence' => $wordData['context_sentence'],
            'audio_pronunciation_url' => $wordData['audio_pronunciation_url'] ?? null,
            'part_of_speech' => $wordData['part_of_speech'] ?? null,
            'difficulty_level' => $wordData['difficulty_level'] ?? 'intermediate',
            'discovered_at' => now(),
            'mastery_level' => 'new',
            'times_reviewed' => 0,
            'is_bookmarked' => false
        ]);
    }

    /**
     * Get vocabulary statistics for a student
     */
    public function getStudentStats(User $student): array
    {
        $vocabulary = ListeningVocabularyDiscovery::where('student_id', $student->id)->get();

        $totalDiscovered = $vocabulary->count();
        $totalMastered = $vocabulary->where('mastery_level', 'mastered')->count();
        $totalBookmarked = $vocabulary->where('is_bookmarked', true)->count();
        $recentDiscoveries = $vocabulary->where('discovered_at', '>=', now()->subWeek())->count();

        $masteryBreakdown = $vocabulary->groupBy('mastery_level')->map(function ($group) {
            return $group->count();
        });

        $masteryPercentage = $totalDiscovered > 0 ? ($totalMastered / $totalDiscovered) * 100 : 0;

        // Difficulty level breakdown
        $difficultyBreakdown = $vocabulary->groupBy('difficulty_level')->map(function ($group) {
            return $group->count();
        });

        return [
            'total_discovered' => $totalDiscovered,
            'total_mastered' => $totalMastered,
            'total_bookmarked' => $totalBookmarked,
            'recent_discoveries' => $recentDiscoveries,
            'mastery_percentage' => round($masteryPercentage, 2),
            'mastery_breakdown' => [
                'new' => $masteryBreakdown->get('new', 0),
                'learning' => $masteryBreakdown->get('learning', 0),
                'familiar' => $masteryBreakdown->get('familiar', 0),
                'mastered' => $masteryBreakdown->get('mastered', 0)
            ],
            'difficulty_breakdown' => [
                'beginner' => $difficultyBreakdown->get('beginner', 0),
                'intermediate' => $difficultyBreakdown->get('intermediate', 0),
                'advanced' => $difficultyBreakdown->get('advanced', 0)
            ]
        ];
    }

    /**
     * Get vocabulary words that need review
     */
    public function getWordsNeedingReview(User $student, int $limit = 10): Collection
    {
        return ListeningVocabularyDiscovery::where('student_id', $student->id)
            ->needsReview()
            ->with(['test'])
            ->orderBy('discovered_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get vocabulary learning progress over time
     */
    public function getLearningProgress(User $student, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $dailyProgress = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');
            
            $discovered = ListeningVocabularyDiscovery::where('student_id', $student->id)
                ->whereDate('discovered_at', $date)
                ->count();
            
            $reviewed = ListeningVocabularyDiscovery::where('student_id', $student->id)
                ->whereDate('updated_at', $date)
                ->where('times_reviewed', '>', 0)
                ->count();
            
            $dailyProgress[$dateString] = [
                'discovered' => $discovered,
                'reviewed' => $reviewed
            ];
        }

        return $dailyProgress;
    }

    /**
     * Get vocabulary recommendations based on test performance
     */
    public function getVocabularyRecommendations(User $student, Test $test): array
    {
        // Get words from this test that the student hasn't discovered yet
        $testQuestions = $test->testQuestions()->with(['passage'])->get();
        $discoveredWords = ListeningVocabularyDiscovery::where('student_id', $student->id)
            ->where('test_id', $test->id)
            ->pluck('word')
            ->toArray();

        $recommendations = [];

        foreach ($testQuestions as $question) {
            if ($question->passage && $question->passage->content) {
                // Extract potential vocabulary words from passage content
                $words = $this->extractVocabularyFromText($question->passage->content);
                
                foreach ($words as $word) {
                    if (!in_array(strtolower($word['word']), $discoveredWords)) {
                        $recommendations[] = [
                            'word' => $word['word'],
                            'definition' => $word['definition'] ?? 'Definition needed',
                            'context' => $word['context'],
                            'difficulty' => $word['difficulty'] ?? 'intermediate',
                            'question_id' => $question->id
                        ];
                    }
                }
            }
        }

        // Limit recommendations and sort by difficulty
        return collect($recommendations)
            ->unique('word')
            ->sortBy('difficulty')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Extract vocabulary words from text content
     * This is a simplified implementation - in production, you might use
     * NLP libraries or external APIs for better word extraction and analysis
     */
    private function extractVocabularyFromText(string $text): array
    {
        $words = [];
        
        // Simple word extraction - split by spaces and punctuation
        $textWords = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($textWords as $word) {
            $cleanWord = strtolower(trim($word));
            
            // Filter out common words and short words
            if (strlen($cleanWord) > 4 && !$this->isCommonWord($cleanWord)) {
                $words[] = [
                    'word' => $cleanWord,
                    'context' => $this->getWordContext($text, $cleanWord),
                    'difficulty' => $this->estimateDifficulty($cleanWord)
                ];
            }
        }

        return array_unique($words, SORT_REGULAR);
    }

    /**
     * Check if a word is a common word that shouldn't be recommended
     */
    private function isCommonWord(string $word): bool
    {
        $commonWords = [
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our',
            'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now', 'old', 'see', 'two', 'way',
            'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use', 'with', 'have', 'this',
            'will', 'your', 'from', 'they', 'know', 'want', 'been', 'good', 'much', 'some', 'time', 'very',
            'when', 'come', 'here', 'just', 'like', 'long', 'make', 'many', 'over', 'such', 'take', 'than',
            'them', 'well', 'were', 'what'
        ];

        return in_array($word, $commonWords);
    }

    /**
     * Get context for a word from the text
     */
    private function getWordContext(string $text, string $word): string
    {
        $sentences = preg_split('/[.!?]+/', $text);
        
        foreach ($sentences as $sentence) {
            if (stripos($sentence, $word) !== false) {
                return trim($sentence);
            }
        }
        
        return '';
    }

    /**
     * Estimate word difficulty based on length and common patterns
     */
    private function estimateDifficulty(string $word): string
    {
        $length = strlen($word);
        
        if ($length <= 5) {
            return 'beginner';
        } elseif ($length <= 8) {
            return 'intermediate';
        } else {
            return 'advanced';
        }
    }

    /**
     * Bulk update mastery levels for multiple words
     */
    public function bulkUpdateMastery(User $student, array $wordIds, string $masteryLevel): int
    {
        return ListeningVocabularyDiscovery::where('student_id', $student->id)
            ->whereIn('id', $wordIds)
            ->update([
                'mastery_level' => $masteryLevel,
                'times_reviewed' => DB::raw('times_reviewed + 1')
            ]);
    }

    /**
     * Export vocabulary list for external study tools
     */
    public function exportVocabulary(User $student, array $filters = []): array
    {
        $query = ListeningVocabularyDiscovery::where('student_id', $student->id)
            ->with(['test']);

        if (isset($filters['mastery_level'])) {
            $query->byMasteryLevel($filters['mastery_level']);
        }

        if (isset($filters['bookmarked_only']) && $filters['bookmarked_only']) {
            $query->bookmarked();
        }

        if (isset($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }

        $vocabulary = $query->orderBy('word')->get();

        return $vocabulary->map(function ($word) {
            return [
                'word' => $word->word,
                'definition' => $word->definition,
                'context' => $word->context_sentence,
                'part_of_speech' => $word->part_of_speech,
                'difficulty' => $word->difficulty_level,
                'mastery_level' => $word->mastery_level,
                'times_reviewed' => $word->times_reviewed,
                'test_name' => $word->test->title,
                'discovered_date' => $word->discovered_at->format('Y-m-d'),
                'audio_url' => $word->audio_pronunciation_url
            ];
        })->toArray();
    }
}