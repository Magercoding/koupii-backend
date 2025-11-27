<?php

namespace App\Services\V1\ReadingTest;

use App\Models\Passage;
use App\Models\TestQuestion;
use App\Helpers\ReadingCleanupHelper;
use Illuminate\Support\Facades\DB;

class ReadingTestDeleteService
{
    /**
     * Delete a passage and all related data.
     */
    public function deletePassage(string $passageId): array
    {
        $passage = Passage::find($passageId);
        if (!$passage) {
            return ['error' => 'Passage not found', 'status' => 404];
        }

        $test = $passage->test;

        if (!auth()->user()->isAdmin() && $test->creator_id !== auth()->id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        DB::transaction(function () use ($passageId, $passage) {

            // Fetch questions for image cleanup
            $questions = TestQuestion::whereHas('questionGroup', function ($query) use ($passageId) {
                $query->where('passage_id', $passageId);
            })->get();

            // Remove all images safely
            foreach ($questions as $question) {
                ReadingCleanupHelper::deleteQuestionImages($question);
            }

            // Delete passage (cascade handles everything else)
            $passage->delete();
        });

        return ['message' => 'Passage deleted successfully', 'status' => 200];
    }

    /**
     * Delete a single question and cleanup empty groups.
     */
    public function deleteQuestion(string $questionId): array
    {
        $question = TestQuestion::find($questionId);
        if (!$question) {
            return ['error' => 'Question not found', 'status' => 404];
        }

        $test = $question->questionGroup->passage->test;

        if (!auth()->user()->isAdmin() && $test->creator_id !== auth()->id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        $group = $question->questionGroup;

        DB::transaction(function () use ($question, $group) {

            // Delete images before DB delete
            ReadingCleanupHelper::deleteQuestionImages($question);

            $question->delete();

            // Delete empty group
            if ($group->questions()->count() === 0) {
                $group->delete();
            }
        });

        return [
            'message' => $group->exists
                ? 'Question deleted successfully'
                : 'Question deleted and empty group removed',
            'group_deleted' => !$group->exists,
            'status' => 200
        ];
    }
}
