<?php

namespace App\Services\V1\SpeakingTask;

use App\Models\Test;
use App\Models\SpeakingSection;
use App\Models\SpeakingTopic;
use App\Models\SpeakingQuestion;
use App\Models\SpeakingTaskAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class SpeakingTaskService
{
    public function getTeacherSpeakingTasks(string $teacherId, array $filters = []): LengthAwarePaginator
    {
        return Test::with(['speakingSections.topics.questions'])
            ->where('creator_id', $teacherId)
            ->where('test_type', 'speaking')
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'published') {
                    $query->where('is_published', true);
                } else {
                    $query->where('is_published', false);
                }
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($filters['difficulty'] ?? null, function ($query, $difficulty) {
                $query->where('difficulty', $difficulty);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createSpeakingTask(array $data): Test
    {
        return DB::transaction(function () use ($data) {
            // Create main test
            $test = Test::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'test_type' => 'speaking',
                'difficulty' => $data['difficulty'] ?? 'beginner',
                'allow_repetition' => $data['allow_repetition'] ?? false,
                'max_repetition_count' => $data['max_repetition_count'] ?? null,
                'timer_type' => $data['timer_type'] ?? 'none',
                'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
                'creator_id' => auth()->id(),
                'is_published' => false
            ]);

            // Create sections, topics, and questions
            foreach ($data['sections'] as $sectionData) {
                $section = $this->createSpeakingSection($test, $sectionData);

                foreach ($sectionData['topics'] as $topicData) {
                    $topic = $this->createSpeakingTopic($section, $topicData);

                    foreach ($topicData['questions'] as $questionData) {
                        $this->createSpeakingQuestion($topic, $questionData);
                    }
                }
            }

            return $test->fresh(['speakingSections.topics.questions']);
        });
    }

    public function updateSpeakingTask(Test $test, array $data): Test
    {
        return DB::transaction(function () use ($test, $data) {
            // Update main test data
            $test->update([
                'title' => $data['title'] ?? $test->title,
                'description' => $data['description'] ?? $test->description,
                'instructions' => $data['instructions'] ?? $test->instructions,
                'difficulty' => $data['difficulty'] ?? $test->difficulty,
                'allow_repetition' => $data['allow_repetition'] ?? $test->allow_repetition,
                'max_repetition_count' => $data['max_repetition_count'] ?? $test->max_repetition_count,
                'timer_type' => $data['timer_type'] ?? $test->timer_type,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? $test->time_limit_seconds,
            ]);

            // Update sections if provided
            if (isset($data['sections'])) {
                // Delete existing sections and recreate (cascade will handle topics/questions)
                $test->speakingSections()->delete();

                foreach ($data['sections'] as $sectionData) {
                    $section = $this->createSpeakingSection($test, $sectionData);

                    foreach ($sectionData['topics'] as $topicData) {
                        $topic = $this->createSpeakingTopic($section, $topicData);

                        foreach ($topicData['questions'] as $questionData) {
                            $this->createSpeakingQuestion($topic, $questionData);
                        }
                    }
                }
            }

            return $test->fresh(['speakingSections.topics.questions']);
        });
    }

    public function deleteSpeakingTask(Test $test): bool
    {
        return DB::transaction(function () use ($test) {
            // Check if there are any submissions for this test
            $hasSubmissions = $test->speakingSubmissions()->exists();

            if ($hasSubmissions) {
                throw new Exception('Cannot delete speaking task that has submissions');
            }

            return $test->delete();
        });
    }

    public function assignToClasses(Test $test, array $data): void
    {
        DB::transaction(function () use ($test, $data) {
            foreach ($data['class_ids'] as $classId) {
                SpeakingTaskAssignment::updateOrCreate(
                    [
                        'test_id' => $test->id,
                        'class_id' => $classId,
                    ],
                    [
                        'assigned_by' => auth()->id(),
                        'due_date' => $data['due_date'] ?? null,
                        'assigned_at' => now(),
                        'allow_retake' => $data['allow_retake'] ?? true,
                        'max_attempts' => $data['max_attempts'] ?? 3,
                    ]
                );
            }
        });
    }

    public function publishTask(Test $test): Test
    {
        // Validate that the task is ready for publishing
        $this->validateTaskForPublishing($test);

        $test->update(['is_published' => true]);

        return $test;
    }

    public function unpublishTask(Test $test): Test
    {
        $test->update(['is_published' => false]);

        return $test;
    }

    public function getSpeakingTaskWithDetails(string $testId): Test
    {
        return Test::with([
            'speakingSections.topics.questions',
            'speakingTaskAssignments.class',
            'speakingTaskAssignments.assignedBy',
            'creator'
        ])->findOrFail($testId);
    }

    public function duplicateSpeakingTask(Test $test): Test
    {
        return DB::transaction(function () use ($test) {
            // Create duplicate test
            $newTest = Test::create([
                'title' => $test->title . ' (Copy)',
                'description' => $test->description,
                'instructions' => $test->instructions,
                'test_type' => $test->test_type,
                'difficulty' => $test->difficulty,
                'allow_repetition' => $test->allow_repetition,
                'max_repetition_count' => $test->max_repetition_count,
                'timer_type' => $test->timer_type,
                'time_limit_seconds' => $test->time_limit_seconds,
                'creator_id' => auth()->id(),
                'is_published' => false
            ]);

            // Duplicate sections, topics, and questions
            foreach ($test->speakingSections as $section) {
                $newSection = $this->createSpeakingSection($newTest, [
                    'section_type' => $section->section_type,
                    'description' => $section->description,
                    'prep_time_seconds' => $section->prep_time_seconds,
                ]);

                foreach ($section->topics as $topic) {
                    $newTopic = $this->createSpeakingTopic($newSection, [
                        'topic_name' => $topic->topic_name,
                    ]);

                    foreach ($topic->questions as $question) {
                        $this->createSpeakingQuestion($newTopic, [
                            'question_number' => $question->question_number,
                            'question_text' => $question->question_text,
                            'time_limit_seconds' => $question->time_limit_seconds,
                        ]);
                    }
                }
            }

            return $newTest->fresh(['speakingSections.topics.questions']);
        });
    }

    public function getTaskStatistics(Test $test): array
    {
        $totalAssignments = $test->speakingTaskAssignments()->count();
        $totalSubmissions = $test->speakingSubmissions()->count();
        $completedSubmissions = $test->speakingSubmissions()->where('status', 'reviewed')->count();
        $pendingSubmissions = $test->speakingSubmissions()->where('status', 'submitted')->count();

        $averageScore = $test->speakingSubmissions()
            ->whereHas('review')
            ->join('speaking_reviews', 'speaking_submissions.id', '=', 'speaking_reviews.submission_id')
            ->avg('speaking_reviews.total_score');

        return [
            'total_assignments' => $totalAssignments,
            'total_submissions' => $totalSubmissions,
            'completed_submissions' => $completedSubmissions,
            'pending_submissions' => $pendingSubmissions,
            'completion_rate' => $totalSubmissions > 0 ? ($completedSubmissions / $totalSubmissions) * 100 : 0,
            'average_score' => $averageScore ? round($averageScore, 2) : null,
        ];
    }

    public function getTaskAssignments(Test $test): Collection
    {
        return $test->speakingTaskAssignments()
            ->with(['class', 'assignedBy'])
            ->latest()
            ->get();
    }

    private function createSpeakingSection(Test $test, array $sectionData): SpeakingSection
    {
        return SpeakingSection::create([
            'test_id' => $test->id,
            'section_type' => $sectionData['section_type'],
            'description' => $sectionData['description'] ?? null,
            'prep_time_seconds' => $sectionData['prep_time_seconds'] ?? null,
        ]);
    }

    private function createSpeakingTopic(SpeakingSection $section, array $topicData): SpeakingTopic
    {
        return SpeakingTopic::create([
            'speaking_section_id' => $section->id,
            'topic_name' => $topicData['topic_name'],
        ]);
    }

    private function createSpeakingQuestion(SpeakingTopic $topic, array $questionData): SpeakingQuestion
    {
        return SpeakingQuestion::create([
            'speaking_topic_id' => $topic->id,
            'question_number' => $questionData['question_number'],
            'question_text' => $questionData['question_text'],
            'time_limit_seconds' => $questionData['time_limit_seconds'],
        ]);
    }

    private function validateTaskForPublishing(Test $test): void
    {
        // Check if test has sections
        if (!$test->speakingSections()->exists()) {
            throw new Exception('Cannot publish speaking task without sections');
        }

        // Check if all sections have topics
        $sectionsWithoutTopics = $test->speakingSections()
            ->doesntHave('topics')
            ->exists();

        if ($sectionsWithoutTopics) {
            throw new Exception('All sections must have at least one topic');
        }

        $topicsWithoutQuestions = SpeakingTopic::whereHas('section', function ($query) use ($test) {
            $query->where('test_id', $test->id);
        })->doesntHave('questions')->exists();

        if ($topicsWithoutQuestions) {
            throw new Exception('All topics must have at least one question');
        }

 
        if (!$test->speakingTaskAssignments()->exists()) {
            throw new Exception('Cannot publish speaking task without class assignments');
        }
    }
}