<?php

namespace Database\Seeders;

use App\Models\Test;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiscoverTestSeeder extends Seeder
{
    public function run()
    {
        // 1. Create a public Reading Test
        $test = Test::create([
            'id' => Str::uuid(),
            'title' => 'Discover Reading Practice (AI Generated)',
            'description' => 'A test passage to verify the Discover feature.',
            'type' => 'reading',
            'test_type' => 'single',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
            'creator_id' => \App\Models\User::first()?->id ?? Str::uuid(),
        ]);

        // 2. Create a Passage
        $passage = Passage::create([
            'id' => Str::uuid(),
            'test_id' => $test->id,
            'title' => 'The Rise of AI',
            'transcript' => '<p>Artificial intelligence is transforming the world...</p>',
            'description' => 'A short passage about AI.',
        ]);

        // 3. Create a Question Group
        $group = QuestionGroup::create([
            'id' => Str::uuid(),
            'passage_id' => $passage->id,
            'title' => 'Multiple Choice',
            'instructions' => 'Choose the correct option.',
            'type' => 'multiple_choice',
        ]);

        // 4. Create a Question
        $question = TestQuestion::create([
            'id' => Str::uuid(),
            'question_group_id' => $group->id,
            'question_number' => 1,
            'question_text' => 'What is transforming the world?',
            'question_type' => 'multiple_choice',
            'points' => 1,
        ]);

        // 5. Create Options
        QuestionOption::create([
            'id' => Str::uuid(),
            'question_id' => $question->id,
            'option_text' => 'Artificial Intelligence',
            'is_correct' => true,
        ]);
        QuestionOption::create([
            'id' => Str::uuid(),
            'question_id' => $question->id,
            'option_text' => 'Mechanical Fans',
            'is_correct' => false,
        ]);

        echo "Created Reading Test: " . $test->id . PHP_EOL;

        // 6. Create a public Speaking Test
        $speakingTest = Test::create([
            'id' => Str::uuid(),
            'title' => 'Discover Speaking Practice (AI Generated)',
            'description' => 'A speaking section to verify the Discover feature.',
            'type' => 'speaking',
            'test_type' => 'single',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
            'creator_id' => $test->creator_id,
        ]);

        \App\Models\SpeakingSection::create([
            'id' => Str::uuid(),
            'test_id' => $speakingTest->id,
            'section_type' => 'introduction',
            'description' => 'Introduce yourself to the AI examiner.',
            'prep_time_seconds' => 30,
        ]);

        echo "Created Speaking Test: " . $speakingTest->id . PHP_EOL;
    }
}
