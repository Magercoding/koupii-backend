<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\Passage;
use App\Models\QuestionGroup;
use App\Models\TestQuestion;
use App\Models\QuestionOption;
use App\Models\SpeakingTask;
use App\Models\SpeakingSection;
use App\Models\SpeakingQuestion;
use App\Models\SpeakingTopic;
use App\Models\WritingTask;
use App\Models\WritingTaskQuestion;
use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\User;
use Illuminate\Support\Str;

class DiscoverTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or Create Admin
        $admin = User::where('role', 'admin')->first() ?: User::factory()->create(['role' => 'admin', 'email' => 'admin@koupii.com']);

        // Clear existing public tests to have a clean slate
        Test::where('is_public', true)->delete();
        \App\Models\ReadingTask::where('is_public', true)->delete();
        \App\Models\ListeningTask::where('is_public', true)->delete();
        \App\Models\WritingTask::where('is_public', true)->delete();
        \App\Models\SpeakingTask::where('is_public', true)->delete();

        // 2. SEED READING (JSON + Full Relational)
        $readingTaskId = (string) Str::uuid();
        $readingTest = Test::create([
            'id' => $readingTaskId,
            'creator_id' => $admin->id,
            'title' => 'The Future of Neural Networks',
            'description' => 'Comprehensive academic reading test about AI evolution.',
            'type' => 'reading',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        $readingTask = \App\Models\ReadingTask::create([
            'id' => $readingTaskId,
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
                    'questionGroups' => [
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

        // RELATIONAL SEEDING FOR EXERCISE VIEW
        $relPassage = $readingTest->passages()->create([
            'id' => Str::uuid(),
            'title' => 'The Evolution of AI',
            'description' => "The concept of neural networks dates back to the mid-20th century...",
        ]);

        $relGroup = $relPassage->questionGroups()->create([
            'id' => Str::uuid(),
            'instruction' => 'Choose the correct option.',
        ]);

        // Question 1 (Relational)
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

        // Question 2 (Relational)
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

        // 3. SEED LISTENING (Relational + passages_data JSON)
        $listeningTaskId = (string) Str::uuid();
        Test::create([
            'id' => $listeningTaskId,
            'creator_id' => $admin->id,
            'title' => 'Library Induction Section 1',
            'description' => 'Practice listening skills with library orientation.',
            'type' => 'listening',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        $listeningTask = ListeningTask::create([
            'id' => $listeningTaskId,
            'created_by' => $admin->id,
            'title' => 'Library Induction Section 1',
            'description' => 'Practice listening skills with library orientation.',
            'task_type' => 'listening',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'instructions' => 'Listen carefully and answer.',
            'passages_data' => [
                [
                    'index' => 0,
                    'instruction' => 'Answer questions 1.',
                    'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                    'transcript' => [
                        'type' => 'conversation',
                        'title' => 'Library Induction',
                        'speakers' => [
                            ['name' => 'Librarian', 'inputs' => [['text' => 'Welcome to the library!']]],
                            ['name' => 'Student', 'inputs' => [['text' => 'Thank you!']]]
                        ]
                    ]
                ]
            ]
        ]);

        ListeningQuestion::create([
            'id' => Str::uuid(),
            'listening_task_id' => $listeningTaskId,
            'question_type' => 'choose_correct_answer',
            'question_text' => 'How many books can a student borrow?',
            'options' => [
                ['id' => 'A', 'text' => '5 books'],
                ['id' => 'B', 'text' => '8 books']
            ],
            'correct_answers' => [['id' => 'B', 'text' => '8 books']],
            'points' => 100,
            'order_index' => 1,
            'passage_index' => 0,
        ]);

        // 4. SEED WRITING (Relational + passages JSON)
        $writingTaskId = (string) Str::uuid();
        Test::create([
            'id' => $writingTaskId,
            'creator_id' => $admin->id,
            'title' => 'Essay: Climate Change',
            'description' => 'Academic writing task.',
            'type' => 'writing',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        WritingTask::create([
            'id' => $writingTaskId,
            'creator_id' => $admin->id,
            'title' => 'Essay: Climate Change',
            'description' => 'Academic writing task.',
            'task_type' => 'essay',
            'is_public' => true,
            'is_published' => true,
            'passages' => [
                [
                    'title' => 'Global Warming Task',
                    'description' => 'Write a 250-word essay.',
                    'questions' => [
                        [
                            'question_number' => 1,
                            'question_text' => 'Discuss the primary causes of global warming.'
                        ]
                    ]
                ]
            ]
        ]);

        WritingTaskQuestion::create([
            'id' => Str::uuid(),
            'writing_task_id' => $writingTaskId,
            'question_number' => 1,
            'question_type' => 'essay',
            'question_text' => 'Discuss the primary causes of global warming.',
        ]);

        // 5. SEED SPEAKING (Full Schema - questions JSON)
        $speakingTaskId = (string) Str::uuid();
        Test::create([
            'id' => $speakingTaskId,
            'creator_id' => $admin->id,
            'title' => 'Daily English Interview',
            'description' => 'General English conversation practice.',
            'type' => 'speaking',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        SpeakingTask::create([
            'id' => $speakingTaskId,
            'created_by' => $admin->id,
            'title' => 'Daily English Interview',
            'description' => 'General English conversation practice.',
            'difficulty_level' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
            'questions' => [
                [
                    'title' => 'Part 1: Introduction',
                    'instructions' => 'General questions about yourself.',
                    'order_index' => 0,
                    'questions' => [
                        [
                            'order_index' => 0,
                            'topic' => 'Personal',
                            'prompt' => 'What is your favorite hobby?'
                        ],
                        [
                            'order_index' => 1,
                            'topic' => 'Routine',
                            'prompt' => 'Describe your typical day.'
                        ]
                    ]
                ]
            ]
        ]);

        $this->command->info('DiscoverTestSeeder: SUCCESSFULLY seeded all 4 skill types with Frontend-Compatible JSON AND Relational data!');
    }
}
