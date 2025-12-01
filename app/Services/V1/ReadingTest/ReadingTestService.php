<?php

namespace App\Services\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Test;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\QuestionOption;
use App\Models\QuestionBreakdown;
use App\Models\HighlightSegment;
use App\Helpers\FileUploadHelper;

class ReadingTestService
{
    /**
     * CREATE READING TEST
     */
    public function create(array $data, Request $request): Test
    {
        return DB::transaction(function () use ($data, $request) {

            $test = Test::create([
                'id' => Str::uuid()->toString(),
                'creator_id' => auth()->id(),
                'title' => $data['title'],
                'type' => $data['type'],
                'difficulty' => $data['difficulty'],
                'test_type' => $data['test_type'],
                'description' => $data['description'] ?? null,
                'timer_mode' => $data['timer_mode'] ?? 'none',
                'timer_settings' => $data['timer_settings'] ?? null,
                'allow_repetition' => $data['allow_repetition'] ?? false,
                'max_repetition_count' => $data['max_repetition_count'] ?? null,
                'is_public' => $data['is_public'] ?? false,
                'is_published' => $data['is_published'] ?? true,
                'settings' => $data['settings'] ?? null,
            ]);

            foreach ($data['passages'] as $pIndex => $pData) {

                $passage = Passage::create([
                    'id' => Str::uuid()->toString(),
                    'test_id' => $test->id,
                    'title' => $pData['title'] ?? null,
                    'description' => $pData['description'] ?? null,
                ]);

                foreach ($pData['question_groups'] as $gIndex => $gData) {

                    $group = QuestionGroup::create([
                        'id' => Str::uuid()->toString(),
                        'passage_id' => $passage->id,
                        'instruction' => $gData['instruction'] ?? null,
                    ]);

                    foreach ($gData['questions'] as $qIndex => $qData) {

                        $question = $this->createQuestion($qData, $group, $request, $pIndex, $gIndex, $qIndex);

                        if (isset($qData['items'])) {
                            foreach ($qData['items'] as $itemData) {
                                $this->createSubQuestion($itemData, $group);
                            }
                        }

                        if (isset($qData['breakdown'])) {
                            $this->createBreakdown($qData['breakdown'], $question);
                        }
                    }
                }
            }

            return $test;
        });
    }

    /**
     * UPDATE READING TEST
     */
    public function update(string $id, Request $request): Test
    {
        return DB::transaction(function () use ($id, $request) {

            $test = $this->updateTestBase($id, $request);

            $this->syncPassages($test, $request);
            $this->syncGroups($test, $request);
            $this->syncQuestions($test, $request);
            $this->cleanupRemoved($test, $request);

            return $test;
        });
    }

    /**
     * CREATE QUESTION (Used by CREATE)
     */
    private function createQuestion($qData, $group, Request $request, $pIndex, $gIndex, $qIndex)
    {
        $imageKey = "passages.$pIndex.question_groups.$gIndex.questions.$qIndex.question_data.images";

        $newImages = [];

        if ($request->hasFile($imageKey)) {
            foreach ($request->file($imageKey) as $file) {
                $newImages[] = FileUploadHelper::upload($file, 'question_images');
            }
        }

        $questionData = $qData['question_data'] ?? [];
        unset($questionData['images']);

        $question = TestQuestion::create([
            'id' => Str::uuid()->toString(),
            'question_group_id' => $group->id,
            'question_type' => $qData['question_type'],
            'question_number' => $qData['question_number'] ?? null,
            'question_text' => $qData['question_text'] ?? null,
            'question_data' => array_merge($questionData, $newImages ? ['image_path' => $newImages] : []),
            'correct_answers' => json_encode($qData['correct_answers'] ?? []),
            'points_value' => $qData['points_value'] ?? 0,
        ]);

        if (isset($qData['options'])) {
            foreach ($qData['options'] as $opt) {
                QuestionOption::create([
                    'id' => Str::uuid()->toString(),
                    'question_id' => $question->id,
                    'option_key' => $opt['option_key'],
                    'option_text' => $opt['option_text'] ?? null,
                ]);
            }
        }

        return $question;
    }

    private function createSubQuestion($itemData, QuestionGroup $group)
    {
        return TestQuestion::create([
            'id' => Str::uuid()->toString(),
            'question_group_id' => $group->id,
            'question_type' => $itemData['question_type'],
            'question_number' => $itemData['question_number'] ?? null,
            'question_text' => $itemData['question_text'] ?? null,
            'question_data' => $itemData['question_data'] ?? [],
            'correct_answers' => json_encode($itemData['correct_answers'] ?? []),
        ]);
    }

    private function createBreakdown($bdData, TestQuestion $question)
    {
        $breakdown = QuestionBreakdown::create([
            'id' => Str::uuid()->toString(),
            'question_id' => $question->id,
            'explanation' => $bdData['explanation'] ?? null,
            'has_highlight' => $bdData['has_highlight'] ?? false,
        ]);

        if (isset($bdData['highlights'])) {
            foreach ($bdData['highlights'] as $h) {
                HighlightSegment::create([
                    'id' => Str::uuid()->toString(),
                    'breakdown_id' => $breakdown->id,
                    'start_char_index' => $h['start_char_index'],
                    'end_char_index' => $h['end_char_index'],
                ]);
            }
        }

        return $breakdown;
    }


    public function updateTest($test, $validated, $request)
    {
        $test->update([
            'difficulty' => $validated['difficulty'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'test_type' => $validated['test_type'] ?? $test->test_type,
            'timer_mode' => $validated['timer_mode'] ?? $test->timer_mode,
            'timer_settings' => $validated['timer_settings'] ?? null,
            'allow_repetition' => $validated['allow_repetition'] ?? false,
            'max_repetition_count' => $validated['max_repetition_count'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'is_published' => $validated['is_published'] ?? $test->is_published,
            'settings' => $validated['settings'] ?? $test->settings,
        ]);

        $this->syncPassages($test, $validated['passages'], $request);
    }

    private function syncPassages($test, $passages, $request)
    {
        $passageIds = [];

        foreach ($passages as $pIndex => $pData) {
            $passage = Passage::updateOrCreate(
                ['id' => $pData['id'] ?? null],
                [
                    'id' => $pData['id'] ?? (string) Str::uuid(),
                    'test_id' => $test->id,
                    'title' => $pData['title'],
                    'description' => $pData['description'] ?? null,
                ]
            );

            $passageIds[] = $passage->id;

            $this->syncGroups($passage, $pData['question_groups'], $request, $pIndex);
        }

        // Cleanup
        Passage::where('test_id', $test->id)->whereNotIn('id', $passageIds)->delete();
    }

    private function syncGroups($passage, $groups, $request, $pIndex)
    {
        $groupIds = [];

        foreach ($groups as $gIndex => $gData) {
            $group = QuestionGroup::updateOrCreate(
                ['id' => $gData['id'] ?? null],
                [
                    'id' => $gData['id'] ?? (string) Str::uuid(),
                    'passage_id' => $passage->id,
                    'instruction' => $gData['instruction'],
                ]
            );

            $groupIds[] = $group->id;

            $this->syncQuestions($group, $gData['questions'], $request, $pIndex, $gIndex);
        }

        QuestionGroup::where('passage_id', $passage->id)->whereNotIn('id', $groupIds)->delete();
    }

    private function syncQuestions($group, $questions, $request, $pIndex, $gIndex)
    {
        $questionIds = [];

        foreach ($questions as $qIndex => $qData) {
            $imageKey = "passages.$pIndex.question_groups.$gIndex.questions.$qIndex.question_data.images";

            $question = TestQuestion::updateOrCreate(
                ['id' => $qData['id'] ?? null],
                [
                    'id' => $qData['id'] ?? (string) Str::uuid(),
                    'question_group_id' => $group->id,
                    'question_type' => $qData['question_type'],
                    'question_number' => $qData['question_number'] ?? null,
                    'question_text' => $qData['question_text'],
                    'points_value' => $qData['points_value'] ?? 0,
                ]
            );

            $questionIds[] = $question->id;

            // Handle image uploads / delete
            $finalPaths = ReadingTestHelper::syncImages($question, $request, $imageKey, $qData['question_data'] ?? []);

            // Update question_data
            $newQData = ($qData['question_data'] ?? []);
            unset($newQData['images'], $newQData['remove_images']);

            $question->update([
                'question_data' => array_merge($newQData, $finalPaths ? ['image_path' => $finalPaths] : []),
                'correct_answers' => json_encode($qData['correct_answers'] ?? []),
            ]);

            $this->syncOptions($question, $qData);
            $this->syncBreakdown($question, $qData);
        }

        // Delete removed questions
        TestQuestion::where('question_group_id', $group->id)
            ->whereNotIn('id', $questionIds)
            ->delete();
    }

    private function syncOptions($question, $qData)
    {
        if (!isset($qData['options']))
            return;

        $optionIds = [];

        foreach ($qData['options'] as $oData) {
            $opt = QuestionOption::updateOrCreate(
                ['id' => $oData['id'] ?? null],
                [
                    'id' => $oData['id'] ?? (string) Str::uuid(),
                    'question_id' => $question->id,
                    'option_key' => $oData['option_key'],
                    'option_text' => $oData['option_text'],
                ]
            );

            $optionIds[] = $opt->id;
        }

        QuestionOption::where('question_id', $question->id)
            ->whereNotIn('id', $optionIds)
            ->delete();
    }

    private function syncBreakdown($question, $qData)
    {
        if (!isset($qData['breakdown']))
            return;

        $break = QuestionBreakdown::updateOrCreate(
            ['question_id' => $question->id],
            [
                'id' => $qData['breakdown']['id'] ?? (string) Str::uuid(),
                'explanation' => $qData['breakdown']['explanation'] ?? null,
                'has_highlight' => $qData['breakdown']['has_highlight'] ?? false,
            ]
        );

        if (!isset($qData['breakdown']['highlights']))
            return;

        $highlightIds = [];
        foreach ($qData['breakdown']['highlights'] as $h) {
            $segment = HighlightSegment::updateOrCreate(
                ['id' => $h['id'] ?? null],
                [
                    'id' => $h['id'] ?? (string) Str::uuid(),
                    'breakdown_id' => $break->id,
                    'start_char_index' => $h['start_char_index'],
                    'end_char_index' => $h['end_char_index'],
                ]
            );
            $highlightIds[] = $segment->id;
        }

        HighlightSegment::where('breakdown_id', $break->id)
            ->whereNotIn('id', $highlightIds)
            ->delete();
    }

  
}
