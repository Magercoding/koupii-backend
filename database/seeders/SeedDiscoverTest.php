<?php

namespace Database\Seeders;

use App\Models\Test;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;

class SeedDiscoverTest extends Seeder
{
    public function run()
    {
        $testId = '02fdbdd3-2b9f-4f1e-976b-dcaa6381f710';
        $test = Test::find($testId);

        if (!$test) {
            $this->command->error("Test with ID $testId not found!");
            return;
        }

        // Create a passage
        $passage = Passage::updateOrCreate(
            ['test_id' => $test->id, 'title' => 'The Future of AI'],
            [
                'description' => 'Artificial Intelligence (AI) is transforming the world in unprecedented ways. From self-driving cars to advanced medical diagnostics, AI is becoming an integral part of our daily lives...',
                'transcript_type' => 'descriptive',
                'transcript' => [
                    ['content' => 'Artificial Intelligence (AI) is transforming the world in unprecedented ways.'],
                    ['content' => 'From self-driving cars to advanced medical diagnostics, AI is becoming an integral part of our daily lives.']
                ]
            ]
        );

        // Create a question group
        $group = QuestionGroup::updateOrCreate(
            ['passage_id' => $passage->id, 'instruction' => 'Choose the correct letter, A, B, C or D.'],
            []
        );

        // Create questions
        $q1 = TestQuestion::updateOrCreate(
            ['question_group_id' => $group->id, 'question_number' => 1],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What is one example of AI mentioned in the text?',
                'correct_answers' => ['A'],
                'points_value' => 1.0
            ]
        );

        QuestionOption::updateOrCreate(
            ['question_id' => $q1->id, 'option_key' => 'A'],
            ['option_text' => 'Self-driving cars']
        );
        QuestionOption::updateOrCreate(
            ['question_id' => $q1->id, 'option_key' => 'B'],
            ['option_text' => 'Flying bicycles']
        );
        QuestionOption::updateOrCreate(
            ['question_id' => $q1->id, 'option_key' => 'C'],
            ['option_text' => 'Time travel']
        );
        QuestionOption::updateOrCreate(
            ['question_id' => $q1->id, 'option_key' => 'D'],
            ['option_text' => 'Teleportation']
        );

        $this->command->info("Data seeded for test $testId");
    }
}
