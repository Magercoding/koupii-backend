<?php

namespace App\Services\V1\Test;

use App\Models\Test;
use App\Models\Classes;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\ListeningTask;
use App\Models\WritingTask;
use App\Events\TestAssignedToClass;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestService
{
    public function createTest(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $test = Test::create([
                    'id' => Str::uuid(),
                    'creator_id' => auth()->id(),
                    'class_id' => $data['class_id'] ?? null,
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

                // If test is published and assigned to a class, automatically create assignments
                if ($test->is_published && $test->class_id) {
                    $this->triggerAutomaticAssignment($test, $data['due_date'] ?? null);
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
                $wasPublished = $test->is_published;
                $hadClassId = $test->class_id;

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
                    'class_id' => $data['class_id'] ?? $test->class_id,
                ]);

                // If test was just published or assigned to a class, trigger automatic assignment
                $justPublished = !$wasPublished && $test->is_published;
                $justAssignedToClass = !$hadClassId && $test->class_id;
                
                if (($justPublished || $justAssignedToClass) && $test->is_published && $test->class_id) {
                    $this->triggerAutomaticAssignment($test);
                }

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
        $userId = auth()->id();

        // 1. Build the base Test query
        $query = Test::query()
            ->where('creator_id', $userId);

        // 2. Build ListeningTask union part
        $listeningQuery = ListeningTask::select([
                'id', 
                'title', 
                'description', 
                DB::raw("'listening' as type"), 
                'difficulty', 
                DB::raw("'listening_task' as test_type"), 
                'timer_type as timer_mode',
                DB::raw("json_object('time_limit_seconds', time_limit_seconds) as timer_settings"),
                'allow_retake as allow_repetition',
                'max_attempts_per_audio as max_repetition_count',
                DB::raw("0 as is_public"),
                'is_published', 
                DB::raw("retake_options as settings"),
                'created_at', 
                'updated_at',
                DB::raw("NULL as class_id"),
                'created_by as creator_id'
            ])
            ->where('created_by', $userId);

        // 3. Build WritingTask union part
        $writingQuery = WritingTask::select([
                'id', 
                'title', 
                'description', 
                DB::raw("'writing' as type"), 
                'difficulty', 
                DB::raw("'writing_task' as test_type"), 
                'timer_type as timer_mode',
                DB::raw("json_object('time_limit_seconds', time_limit_seconds) as timer_settings"),
                'allow_retake as allow_repetition',
                'max_retake_attempts as max_repetition_count',
                DB::raw("0 as is_public"),
                'is_published', 
                DB::raw("retake_options as settings"),
                'created_at', 
                'updated_at',
                DB::raw("NULL as class_id"),
                'creator_id as creator_id'
            ])
            ->where('creator_id', $userId);

        // Apply filters to EACH query part before union for better performance
        $requestedType = $filters['type'] ?? null;
        $activeQueries = [];

        // Determine which tables to query based on type filter
        if (!$requestedType || in_array($requestedType, ['reading', 'speaking'])) {
            $activeQueries['test'] = $query->select([
                'id', 'title', 'description', 'type', 'difficulty', 'test_type', 
                'timer_mode', 'timer_settings', 'allow_repetition', 'max_repetition_count',
                'is_public', 'is_published', 'settings', 'created_at', 'updated_at', 
                'class_id', 'creator_id'
            ]);
        }
        
        if (!$requestedType || $requestedType === 'listening') {
            $activeQueries['listening'] = $listeningQuery;
        }

        if (!$requestedType || $requestedType === 'writing') {
            $activeQueries['writing'] = $writingQuery;
        }

        // Apply shared filters to each active query
        foreach ($activeQueries as $key => $q) {
            if (isset($filters['difficulty']) && in_array($filters['difficulty'], ['beginner', 'intermediate', 'advanced'])) {
                $q->where('difficulty', $filters['difficulty']);
            }
            if (isset($filters['is_published'])) {
                $q->where('is_published', $filters['is_published']);
            }
            if (isset($filters['search'])) {
                $q->where('title', 'like', '%' . $filters['search'] . '%');
            }
            if (isset($filters['class_id']) && $key === 'test') {
                $q->where('class_id', $filters['class_id']);
            }
        }

        // Combine using unionAll
        $finalQuery = null;
        $first = true;

        foreach ($activeQueries as $q) {
            if ($first) {
                $finalQuery = $q;
                $first = false;
            } else {
                $finalQuery->unionAll($q);
            }
        }

        // If no queries are active (shouldn't happen with logic above), return empty result
        if (!$finalQuery) {
            return Test::whereRaw('1 = 0');
        }

        // Return the combined query, ordered by latest
        // We use fromSub to allow further operations like pagination and relations on the combined set
        return Test::fromSub($finalQuery, 'combined')
            ->with(['class', 'creator'])
            ->latest('created_at');
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

    /**
     * Trigger automatic assignment creation when test is published and assigned to a class
     */
    private function triggerAutomaticAssignment(Test $test, ?string $dueDate = null)
    {
        try {
            $class = Classes::find($test->class_id);
            if ($class) {
                TestAssignedToClass::dispatch($test, $class, [
                    'title' => $test->title,
                    'description' => 'Complete this test by the due date',
                    'due_date' => $dueDate ? \Carbon\Carbon::parse($dueDate) : now()->addDays(7),
                    'is_published' => true
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to create automatic assignment', [
                'test_id' => $test->id,
                'class_id' => $test->class_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}