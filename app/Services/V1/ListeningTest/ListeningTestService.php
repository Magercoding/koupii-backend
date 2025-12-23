<?php

namespace App\Services\V1\ListeningTest;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListeningTestService
{
    /**
     * Create a new listening test.
     */
    public function createTest(array $data): Test
    {
        return DB::transaction(function () use ($data) {
            $test = Test::create([
                'id' => Str::uuid(),
                'creator_id' => Auth::id(),
                'type' => 'listening',
                'test_type' => $data['test_type'] ?? 'general',
                'difficulty' => $data['difficulty'] ?? 'intermediate',
                'title' => $data['title'],
                'description' => $data['description'],
                'timer_mode' => $data['timer_mode'] ?? 'none',
                'timer_settings' => $data['timer_settings'] ?? [],
                'allow_repetition' => $data['allow_repetition'] ?? false,
                'max_repetition_count' => $data['max_repetition_count'] ?? 1,
                'is_public' => $data['is_public'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'settings' => $data['settings'] ?? [],
            ]);

            return $test->load('creator');
        });
    }

    /**
     * Update an existing listening test.
     */
    public function updateTest(Test $test, array $data): Test
    {
        return DB::transaction(function () use ($test, $data) {
            $test->update([
                'title' => $data['title'] ?? $test->title,
                'description' => $data['description'] ?? $test->description,
                'test_type' => $data['test_type'] ?? $test->test_type,
                'difficulty' => $data['difficulty'] ?? $test->difficulty,
                'timer_mode' => $data['timer_mode'] ?? $test->timer_mode,
                'timer_settings' => $data['timer_settings'] ?? $test->timer_settings,
                'allow_repetition' => $data['allow_repetition'] ?? $test->allow_repetition,
                'max_repetition_count' => $data['max_repetition_count'] ?? $test->max_repetition_count,
                'is_public' => $data['is_public'] ?? $test->is_public,
                'is_published' => $data['is_published'] ?? $test->is_published,
                'settings' => $data['settings'] ?? $test->settings,
            ]);

            // Handle audio segments if provided
            if (isset($data['audio_segments'])) {
                $this->handleAudioSegments($test, $data['audio_segments']);
            }

            // Handle questions if provided
            if (isset($data['questions'])) {
                $this->handleQuestions($test, $data['questions']);
            }

            return $test->load('creator', 'listeningAudioSegments', 'listeningQuestions');
        });
    }

    /**
     * Handle audio segments for the test.
     */
    private function handleAudioSegments(Test $test, array $segments): void
    {
        foreach ($segments as $segmentData) {
            if (isset($segmentData['id'])) {
                // Update existing segment
                $segment = $test->listeningAudioSegments()->find($segmentData['id']);
                if ($segment) {
                    $segment->update([
                        'title' => $segmentData['title'],
                        'audio_url' => $segmentData['audio_url'],
                        'transcript' => $segmentData['transcript'] ?? null,
                        'duration' => $segmentData['duration'] ?? null,
                        'segment_type' => $segmentData['segment_type'],
                        'difficulty_level' => $segmentData['difficulty_level'] ?? null,
                    ]);
                }
            } else {
                // Create new segment
                $test->listeningAudioSegments()->create([
                    'id' => Str::uuid(),
                    'title' => $segmentData['title'],
                    'audio_url' => $segmentData['audio_url'],
                    'transcript' => $segmentData['transcript'] ?? null,
                    'duration' => $segmentData['duration'] ?? null,
                    'segment_type' => $segmentData['segment_type'],
                    'difficulty_level' => $segmentData['difficulty_level'] ?? null,
                ]);
            }
        }
    }

    /**
     * Handle questions for the test.
     */
    private function handleQuestions(Test $test, array $questions): void
    {
        foreach ($questions as $questionData) {
            if (isset($questionData['id'])) {
                // Update existing question
                $question = $test->listeningQuestions()->find($questionData['id']);
                if ($question) {
                    $question->update([
                        'audio_segment_id' => $questionData['audio_segment_id'],
                        'question_text' => $questionData['question_text'],
                        'question_type' => $questionData['question_type'],
                        'time_range' => $questionData['time_range'] ?? null,
                        'options' => $questionData['options'] ?? null,
                        'correct_answer' => $questionData['correct_answer'] ?? null,
                        'explanation' => $questionData['explanation'] ?? null,
                        'points' => $questionData['points'] ?? 1,
                    ]);
                }
            } else {
                // Create new question
                $test->listeningQuestions()->create([
                    'id' => Str::uuid(),
                    'audio_segment_id' => $questionData['audio_segment_id'],
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'time_range' => $questionData['time_range'] ?? null,
                    'options' => $questionData['options'] ?? null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'explanation' => $questionData['explanation'] ?? null,
                    'points' => $questionData['points'] ?? 1,
                ]);
            }
        }
    }

    /**
     * Search tests based on criteria.
     */
    public function searchTests(array $criteria)
    {
        $query = Test::where('type', 'listening')->with(['creator']);

        if (!empty($criteria['title'])) {
            $query->where('title', 'LIKE', '%' . $criteria['title'] . '%');
        }

        if (!empty($criteria['difficulty'])) {
            $query->where('difficulty', $criteria['difficulty']);
        }

        if (!empty($criteria['test_type'])) {
            $query->where('test_type', $criteria['test_type']);
        }

        if (!empty($criteria['creator_id'])) {
            $query->where('creator_id', $criteria['creator_id']);
        }

        if (isset($criteria['is_published'])) {
            $query->where('is_published', $criteria['is_published']);
        }

        // Apply role-based filtering
        $user = Auth::user();
        if ($user->role === 'student') {
            $query->where('is_published', true);
        } elseif ($user->role !== 'admin') {
            $query->where('creator_id', $user->id);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get test statistics.
     */
    public function getTestStatistics(Test $test): array
    {
        return [
            'total_attempts' => $test->submissions()->count(),
            'completed_attempts' => $test->submissions()->where('status', 'completed')->count(),
            'average_score' => $test->submissions()->where('status', 'completed')->avg('score'),
            'average_completion_time' => $test->submissions()->where('status', 'completed')->avg('time_spent'),
            'difficulty_rating' => $this->calculateDifficultyRating($test),
        ];
    }

    /**
     * Calculate difficulty rating based on submission data.
     */
    private function calculateDifficultyRating(Test $test): ?float
    {
        $avgScore = $test->submissions()->where('status', 'completed')->avg('score');
        
        if ($avgScore === null) {
            return null;
        }

        // Convert score to difficulty rating (higher score = lower difficulty)
        return 5 - ($avgScore / 20); // Assuming score is out of 100
    }
}