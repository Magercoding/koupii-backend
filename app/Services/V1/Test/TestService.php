<?php

namespace App\Services\V1\Test;

use App\Models\Test;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TestService
{
    public function createTest(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $test = Test::create([
                    'id' => Str::uuid(),
                    'creator_id' => auth()->id(),
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => $data['type'],
                    'difficulty' => $data['difficulty'],
                    'test_type' => $data['test_type'] ?? 'single',
                    'timer_mode' => $data['timer_mode'] ?? 'none',
                    'timer_settings' => $data['timer_settings'] ?? null,
                    'allow_repetition' => $data['allow_repetition'] ?? false,
                    'max_repetition_count' => $data['max_repetition_count'] ?? null,
                    'is_public' => $data['is_public'] ?? false,
                    'is_published' => $data['is_published'] ?? false,
                    'settings' => $data['settings'] ?? null,
                ]);

                // Create passages if provided
                if (isset($data['passages'])) {
                    $this->createPassages($test, $data['passages']);
                }

                return $test->load(['passages.questionGroups.questions.options']);
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to create test: ' . $e->getMessage());
        }
    }

    public function updateTest(Test $test, array $data)
    {
        try {
            return DB::transaction(function () use ($test, $data) {
                $test->update([
                    'title' => $data['title'] ?? $test->title,
                    'description' => $data['description'] ?? $test->description,
                    'type' => $data['type'] ?? $test->type,
                    'difficulty' => $data['difficulty'] ?? $test->difficulty,
                    'test_type' => $data['test_type'] ?? $test->test_type,
                    'timer_mode' => $data['timer_mode'] ?? $test->timer_mode,
                    'timer_settings' => $data['timer_settings'] ?? $test->timer_settings,
                    'allow_repetition' => $data['allow_repetition'] ?? $test->allow_repetition,
                    'max_repetition_count' => $data['max_repetition_count'] ?? $test->max_repetition_count,
                    'is_public' => $data['is_public'] ?? $test->is_public,
                    'is_published' => $data['is_published'] ?? $test->is_published,
                    'settings' => $data['settings'] ?? $test->settings,
                ]);

                return $test->load(['passages.questionGroups.questions.options']);
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to update test: ' . $e->getMessage());
        }
    }

    public function duplicateTest(Test $test)
    {
        try {
            return DB::transaction(function () use ($test) {
                $newTest = $test->replicate();
                $newTest->id = Str::uuid();
                $newTest->title = $test->title . ' (Copy)';
                $newTest->is_published = false;
                $newTest->creator_id = auth()->id();
                $newTest->save();

                // Duplicate passages and their related data
                foreach ($test->passages as $passage) {
                    $this->duplicatePassage($passage, $newTest->id);
                }

                return $newTest->load(['passages.questionGroups.questions.options']);
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to duplicate test: ' . $e->getMessage());
        }
    }

    public function deleteTest(Test $test)
    {
        try {
            // Check authorization
            if ($test->creator_id !== auth()->id()) {
                throw new \Exception('Unauthorized to delete this test');
            }

            $test->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete test: ' . $e->getMessage());
        }
    }

    public function getTestWithQuestions(Test $test)
    {
        return $test->load(['passages.questionGroups.questions.options']);
    }

    public function getTestsForUser($filters = [])
    {
        $query = Test::with(['passages.questionGroups.questions'])
            ->where('creator_id', auth()->id());

        // Apply filters
        if (isset($filters['type']) && in_array($filters['type'], ['reading', 'listening', 'speaking', 'writing'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['difficulty']) && in_array($filters['difficulty'], ['beginner', 'intermediate', 'advanced'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest();
    }

    private function createPassages(Test $test, array $passages)
    {
        foreach ($passages as $passageData) {
            $passage = $test->passages()->create([
                'id' => Str::uuid(),
                'title' => $passageData['title'] ?? null,
                'description' => $passageData['description'] ?? null,
                'audio_file_path' => $passageData['audio_file_path'] ?? null,
                'transcript_type' => $passageData['transcript_type'] ?? null,
                'transcript' => $passageData['transcript'] ?? null,
            ]);

            if (isset($passageData['question_groups'])) {
                $this->createQuestionGroups($passage, $passageData['question_groups']);
            }
        }
    }

    private function createQuestionGroups(Passage $passage, array $questionGroups)
    {
        foreach ($questionGroups as $groupData) {
            $questionGroup = $passage->questionGroups()->create([
                'id' => Str::uuid(),
                'instruction' => $groupData['instruction'] ?? null,
            ]);

            if (isset($groupData['questions'])) {
                $this->createQuestions($questionGroup, $groupData['questions']);
            }
        }
    }

    private function createQuestions(QuestionGroup $questionGroup, array $questions)
    {
        foreach ($questions as $questionData) {
            $question = $questionGroup->questions()->create([
                'id' => Str::uuid(),
                'question_type' => $questionData['question_type'],
                'question_number' => $questionData['question_number'] ?? null,
                'question_text' => $questionData['question_text'] ?? null,
                'question_data' => $questionData['question_data'] ?? null,
                'correct_answers' => $questionData['correct_answers'] ?? null,
                'points_value' => $questionData['points_value'] ?? 1,
            ]);

            if (isset($questionData['options'])) {
                foreach ($questionData['options'] as $optionData) {
                    $question->options()->create([
                        'id' => Str::uuid(),
                        'option_key' => $optionData['option_key'] ?? null,
                        'option_text' => $optionData['option_text'] ?? null,
                    ]);
                }
            }
        }
    }

    private function duplicatePassage(Passage $passage, string $newTestId)
    {
        $newPassage = $passage->replicate();
        $newPassage->id = Str::uuid();
        $newPassage->test_id = $newTestId;
        $newPassage->save();

        foreach ($passage->questionGroups as $group) {
            $this->duplicateQuestionGroup($group, $newPassage->id);
        }
    }

    private function duplicateQuestionGroup(QuestionGroup $group, string $newPassageId)
    {
        $newGroup = $group->replicate();
        $newGroup->id = Str::uuid();
        $newGroup->passage_id = $newPassageId;
        $newGroup->save();

        foreach ($group->questions as $question) {
            $this->duplicateQuestion($question, $newGroup->id);
        }
    }

    private function duplicateQuestion(TestQuestion $question, string $newGroupId)
    {
        $newQuestion = $question->replicate();
        $newQuestion->id = Str::uuid();
        $newQuestion->question_group_id = $newGroupId;
        $newQuestion->save();

        foreach ($question->options as $option) {
            $newOption = $option->replicate();
            $newOption->id = Str::uuid();
            $newOption->question_id = $newQuestion->id;
            $newOption->save();
        }
    }
}