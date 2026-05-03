<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\ReadingTask;
use App\Models\User;
use Illuminate\Support\Str;

class DiscoverReadingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        // Cleanup
        Test::where('type', 'reading')->where('is_public', true)->delete();
        ReadingTask::where('is_public', true)->delete();

        $id = (string) Str::uuid();
        
        $test = Test::create([
            'id' => $id,
            'creator_id' => $admin->id,
            'title' => 'The Future of Neural Networks',
            'description' => 'Comprehensive academic reading test about AI evolution.',
            'type' => 'reading',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        ReadingTask::create([
            'id' => $id,
            'created_by' => $admin->id,
            'title' => 'The Future of Neural Networks',
            'description' => 'Comprehensive academic reading test about AI evolution.',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
            'passages' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'The Evolution of AI',
                    'description' => 'Historical context of machine learning.',
                    'content' => "The concept of neural networks dates back to the mid-20th century...",
                    'question_groups' => [
                        [
                            'id' => (string) Str::uuid(),
                            'instruction' => 'Choose the correct option.',
                            'questions' => [
                                [
                                    'id' => (string) Str::uuid(),
                                    'question_number' => 1,
                                    'question_text' => 'What was the primary inspiration for early neural networks?',
                                    'question_type' => 'choose_correct_answer',
                                    'points_value' => 50,
                                    'options' => [
                                        ['option_key' => 'A', 'option_text' => 'Clockwork mechanisms'],
                                        ['option_key' => 'B', 'option_text' => 'Biological brain structures'],
                                    ],
                                    'correct_answer' => ['option_key' => 'B', 'option_text' => 'Biological brain structures']
                                ],
                                [
                                    'id' => (string) Str::uuid(),
                                    'question_number' => 2,
                                    'question_text' => 'AGI is already fully achieved.',
                                    'question_type' => 'true_false_not_given',
                                    'points_value' => 50,
                                    'options' => [
                                        ['option_key' => 'T', 'option_text' => 'True'],
                                        ['option_key' => 'F', 'option_text' => 'False'],
                                        ['option_key' => 'N', 'option_text' => 'Not Given']
                                    ],
                                    'correct_answer' => ['option_key' => 'F', 'option_text' => 'False']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Relational data for Exercise view
        $relPassage = $test->passages()->create([
            'id' => Str::uuid(),
            'title' => 'The Evolution of AI',
            'description' => "The concept of neural networks dates back to the mid-20th century...",
        ]);

        $relGroup = $relPassage->questionGroups()->create([
            'id' => Str::uuid(),
            'instruction' => 'Choose the correct option.',
        ]);

        $q1 = $relGroup->questions()->create([
            'id' => Str::uuid(),
            'question_number' => 1,
            'question_text' => 'What was the primary inspiration for early neural networks?',
            'question_type' => 'choose_correct_answer',
            'correct_answers' => 'B',
            'points_value' => 50,
        ]);
        $q1->options()->create(['id' => Str::uuid(), 'option_key' => 'A', 'option_text' => 'Clockwork mechanisms']);
        $q1->options()->create(['id' => Str::uuid(), 'option_key' => 'B', 'option_text' => 'Biological brain structures']);

        $q2 = $relGroup->questions()->create([
            'id' => Str::uuid(),
            'question_number' => 2,
            'question_text' => 'AGI is already fully achieved.',
            'question_type' => 'true_false_not_given',
            'correct_answers' => 'F',
            'points_value' => 50,
        ]);
        $q2->options()->create(['id' => Str::uuid(), 'option_key' => 'T', 'option_text' => 'True']);
        $q2->options()->create(['id' => Str::uuid(), 'option_key' => 'F', 'option_text' => 'False']);
        $q2->options()->create(['id' => Str::uuid(), 'option_key' => 'N', 'option_text' => 'Not Given']);
    }
}
