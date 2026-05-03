<?php

namespace App\Services\V1\Test;

use App\Models\Test;
use App\Models\Classes;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\ListeningTask;
use App\Models\WritingTask;
use App\Models\SpeakingTask;
use App\Models\ReadingTask;
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

                // Auto-assign only when explicitly requested.
                // Creating a test for a class should not immediately create an Assignment unless the client opts in.
                $assignOnCreate = (bool) ($data['assign_on_create'] ?? false);
                if ($assignOnCreate && $test->is_published && $test->class_id) {
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

                // If test was just published or assigned to a class, trigger automatic assignment (opt-in only)
                $justPublished = !$wasPublished && $test->is_published;
                $justAssignedToClass = !$hadClassId && $test->class_id;

                $assignOnCreate = (bool) ($data['assign_on_create'] ?? false);
                if ($assignOnCreate && ($justPublished || $justAssignedToClass) && $test->is_published && $test->class_id) {
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

    /**
     * Find a test or task by ID across all tables
     */
    public function findAnyTaskById(string $id)
    {
        // 1. Try legacy Test model
        $test = Test::find($id);
        if ($test) {
            return $this->getTestWithQuestions($test);
        }

        // 2. Try ReadingTask
        $reading = ReadingTask::find($id);
        if ($reading) {
            $reading->type = 'reading';
            return $reading;
        }

        // 3. Try ListeningTask
        $listening = ListeningTask::find($id);
        if ($listening) {
            $listening->type = 'listening';
            return $listening;
        }

        // 4. Try WritingTask
        $writing = WritingTask::find($id);
        if ($writing) {
            $writing->type = 'writing';
            return $writing;
        }

        // 5. Try SpeakingTask
        $speaking = SpeakingTask::find($id);
        if ($speaking) {
            $speaking->type = 'speaking';
            return $speaking;
        }

        return null;
    }

    public function getTestWithQuestions($test)
    {
        if ($test instanceof Test) {
            return $test->load(['passages.questionGroups.questions.options']);
        }
        
        return $test;
    }

    public function getTestsForUser($filters = [])
    {
        $userId = auth('sanctum')->id();
        $requestedType = $filters['type'] ?? null;
        $isPublicSearch = (isset($filters['is_public']) && $filters['is_public']) || 
                         (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student' && !isset($filters['class_id']));

        $columns = [
            'id', 'title', 'description', 'type', 'difficulty',
            'is_published', 'created_at', 'updated_at', 'creator_id',
            'test_type', 'timer_mode', 'timer_settings', 'allow_repetition',
            'max_repetition_count', 'is_public', 'settings', 'class_id'
        ];

        // Shared secondary filters (search, is_published, class_id)
        $applySharedFilters = function ($q, $difficultyColumn = 'difficulty') use ($filters) {
            if (!empty($filters['search'])) {
                $q->where('title', 'like', '%' . $filters['search'] . '%');
            }
            if (isset($filters['is_published']) && $filters['is_published'] !== null) {
                $q->where('is_published', (bool)$filters['is_published']);
            }
            if (!empty($filters['difficulty'])) {
                $q->where($difficultyColumn, $filters['difficulty']);
            }
            if (!empty($filters['class_id'])) {
                $q->where('class_id', $filters['class_id']);
            }
        };

        // 1. Base Test Query (generic tests table)
        $testQuery = Test::select(array_merge(array_diff($columns, ['class_id']), [
                DB::raw("COALESCE(class_id, NULL) as class_id"),
            ]));
        
        if ($isPublicSearch) {
            $testQuery->where('is_public', true);
            if (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student') {
                $testQuery->where('is_published', true);
            }
        } else {
            $testQuery->where('creator_id', $userId);
        }

        if ($requestedType) {
            $testQuery->where('type', $requestedType);
        }
        $applySharedFilters($testQuery, 'difficulty');

        // 2. Specialized Task Queries
        $activeQueries = [$testQuery];

        // Reading Tasks
        if (!$requestedType || $requestedType === 'reading') {
            $q = ReadingTask::select([
                'id', 'title', 'description',
                DB::raw("'reading' as type"),
                DB::raw("COALESCE(difficulty, difficulty_level, 'beginner') as difficulty"),
                'is_published', 'created_at', 'updated_at',
                'created_by as creator_id',
                DB::raw("'single' as test_type"),
                DB::raw("COALESCE(timer_type, 'none') as timer_mode"),
                DB::raw("CAST(time_limit_seconds AS CHAR) as timer_settings"),
                DB::raw("COALESCE(allow_retake, 0) as allow_repetition"),
                DB::raw("COALESCE(max_retake_attempts, 0) as max_repetition_count"),
                'is_public',
                DB::raw("NULL as settings"),
                'class_id',
            ]);
            
            if ($isPublicSearch) {
                $q->where('is_public', true);
                if (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student') $q->where('is_published', true);
            } else {
                $q->where('created_by', $userId);
            }
            $applySharedFilters($q, 'difficulty'); // ReadingTask has 'difficulty' column
            $activeQueries[] = $q;
        }

        // Listening Tasks
        if (!$requestedType || $requestedType === 'listening') {
            $q = ListeningTask::select([
                'id', 'title', 'description',
                DB::raw("'listening' as type"),
                DB::raw("COALESCE(difficulty, difficulty_level, 'beginner') as difficulty"),
                'is_published', 'created_at', 'updated_at',
                'created_by as creator_id',
                DB::raw("'single' as test_type"),
                DB::raw("COALESCE(timer_type, 'none') as timer_mode"),
                DB::raw("CAST(time_limit_seconds AS CHAR) as timer_settings"),
                DB::raw("COALESCE(allow_retake, 0) as allow_repetition"),
                DB::raw("COALESCE(max_retake_attempts, 0) as max_repetition_count"),
                'is_public',
                DB::raw("NULL as settings"),
                'class_id',
            ]);

            if ($isPublicSearch) {
                $q->where('is_public', true);
                if (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student') $q->where('is_published', true);
            } else {
                $q->where('created_by', $userId);
            }
            $applySharedFilters($q, 'difficulty');
            $activeQueries[] = $q;
        }

        // Writing Tasks
        if (!$requestedType || $requestedType === 'writing') {
            $q = WritingTask::select([
                'id', 'title', 'description',
                DB::raw("'writing' as type"),
                DB::raw("COALESCE(difficulty, 'beginner') as difficulty"),
                'is_published', 'created_at', 'updated_at',
                'creator_id',
                DB::raw("'single' as test_type"),
                DB::raw("COALESCE(timer_type, 'none') as timer_mode"),
                DB::raw("CAST(time_limit_seconds AS CHAR) as timer_settings"),
                DB::raw("COALESCE(allow_retake, 0) as allow_repetition"),
                DB::raw("COALESCE(max_retake_attempts, 0) as max_repetition_count"),
                'is_public',
                DB::raw("NULL as settings"),
                'class_id',
            ]);

            if ($isPublicSearch) {
                $q->where('is_public', true);
                if (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student') $q->where('is_published', true);
            } else {
                $q->where('creator_id', $userId);
            }
            $applySharedFilters($q, 'difficulty');
            $activeQueries[] = $q;
        }

        // Speaking Tasks
        if (!$requestedType || $requestedType === 'speaking') {
            $q = SpeakingTask::select([
                'id', 'title', 'description',
                DB::raw("'speaking' as type"),
                DB::raw("COALESCE(difficulty_level, 'beginner') as difficulty"),
                'is_published', 'created_at', 'updated_at',
                'created_by as creator_id',
                DB::raw("'single' as test_type"),
                DB::raw("'none' as timer_mode"),
                DB::raw("CAST(time_limit_seconds AS CHAR) as timer_settings"),
                DB::raw("0 as allow_repetition"),
                DB::raw("0 as max_repetition_count"),
                'is_public',
                DB::raw("NULL as settings"),
                'class_id',
            ]);

            if ($isPublicSearch) {
                $q->where('is_public', true);
                if (auth('sanctum')->check() && auth('sanctum')->user()->role === 'student') $q->where('is_published', true);
            } else {
                $q->where('created_by', $userId);
            }
            $applySharedFilters($q, 'difficulty_level'); // Note: SpeakingTask uses 'difficulty_level'
            $activeQueries[] = $q;
        }

        // 3. Build final Union query
        $first = array_shift($activeQueries);
        $finalUnion = $first->toBase();
        foreach ($activeQueries as $q) {
            $finalUnion->union($q->toBase());
        }

        $sql = $finalUnion->toSql();
        $bindings = $finalUnion->getBindings();

        // Subquery to get the first assigned class per item via assignments table
        $assignedClassSub = DB::table('assignments')
            ->select('assignments.test_id as item_id', 'classes.name as class_name')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->whereNotNull('assignments.test_id')
            ->union(
                DB::table('assignments')
                    ->select('assignments.task_id as item_id', 'classes.name as class_name')
                    ->join('classes', 'assignments.class_id', '=', 'classes.id')
                    ->whereNotNull('assignments.task_id')
            );

        $assignedClassSql = $assignedClassSub->toSql();
        $assignedClassBindings = $assignedClassSub->getBindings();

        return DB::table(DB::raw("({$sql}) as combined"))
            ->setBindings(array_merge($bindings, $assignedClassBindings))
            ->leftJoin('classes', 'combined.class_id', '=', 'classes.id')
            ->leftJoin(
                DB::raw("(SELECT item_id, MIN(class_name) as class_name FROM ({$assignedClassSql}) as ac GROUP BY item_id) as assigned_class"),
                'assigned_class.item_id', '=', 'combined.id'
            )
            ->select(
                'combined.*',
                DB::raw("COALESCE(classes.name, assigned_class.class_name) as class_name")
            )
            ->orderByDesc('combined.created_at');
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