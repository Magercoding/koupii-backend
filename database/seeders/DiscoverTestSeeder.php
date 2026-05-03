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

        // 2. SEED READING (New ReadingTask format)
        $readingTaskId = (string) Str::uuid();
        
        // Register this task in the global tests table FIRST for Discover visibility
        Test::create([
            'id' => $readingTaskId,
            'creator_id' => $admin->id,
            'title' => 'The Future of Neural Networks',
            'description' => 'Academic reading test about Artificial Intelligence.',
            'type' => 'reading',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        $readingTask = \App\Models\ReadingTask::create([
            'id' => $readingTaskId,
            'created_by' => $admin->id,
            'title' => 'The Future of Neural Networks',
            'description' => 'Academic reading test about Artificial Intelligence.',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
            'passages' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'The Evolution of AI',
                    'content' => "The concept of neural networks dates back to the mid-20th century, inspired by the biological structures of the human brain. Early attempts, such as the Perceptron, laid the groundwork for what would eventually become the backbone of modern machine learning. However, it wasn't until the advent of deep learning and increased computational power that AI truly began to surpass human capabilities in specific domains.\n\nResearchers argue that the next frontier lies in General Artificial Intelligence (AGI), where machines can perform any intellectual task that a human can. While we are still decades away from achieving this, the ethical implications are already being debated in academic and political circles. Issues of bias, transparency, and the displacement of human labor are at the forefront of the conversation.",
                    'questionGroups' => [
                        [
                            'id' => (string) Str::uuid(),
                            'instruction' => 'Choose the correct option for each question based on the passage above.',
                            'questions' => [
                                [
                                    'id' => (string) Str::uuid(),
                                    'question_number' => 1,
                                    'question_text' => 'What was the primary inspiration for early neural networks?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['key' => 'A', 'text' => 'Clockwork mechanisms'],
                                        ['key' => 'B', 'text' => 'Biological brain structures'],
                                        ['key' => 'C', 'text' => 'Electronic circuits'],
                                        ['key' => 'D', 'text' => 'Mathematical logic only']
                                    ],
                                    'correct_answer' => 'B'
                                ],
                                [
                                    'id' => (string) Str::uuid(),
                                    'question_number' => 2,
                                    'question_text' => 'Is AGI currently achievable according to the passage?',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['key' => 'T', 'text' => 'True'],
                                        ['key' => 'F', 'text' => 'False']
                                    ],
                                    'correct_answer' => 'F'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // 3. SEED LISTENING (ListeningTask with Questions)
        $listeningTaskId = (string) Str::uuid();

        Test::create([
            'id' => $listeningTaskId,
            'creator_id' => $admin->id,
            'title' => 'Library Orientation Conversation',
            'description' => 'Listen to a conversation about library services.',
            'type' => 'listening',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        $listeningTask = ListeningTask::create([
            'id' => $listeningTaskId,
            'created_by' => $admin->id,
            'title' => 'Library Orientation Conversation',
            'description' => 'Listen to a conversation about library services.',
            'task_type' => 'conversation',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'instructions' => 'Listen carefully and answer the following questions.',
        ]);

        ListeningQuestion::create([
            'id' => Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'order_index' => 1,
            'question_type' => 'multiple_choice',
            'question_text' => 'How many books can a new student borrow?',
            'options' => [
                ['key' => 'A', 'text' => '5 books'],
                ['key' => 'B', 'text' => '8 books'],
                ['key' => 'C', 'text' => '10 books']
            ],
            'correct_answers' => ['B'],
            'points' => 1,
        ]);

        // 4. SEED WRITING
        $writingTaskId = (string) Str::uuid();

        Test::create([
            'id' => $writingTaskId,
            'creator_id' => $admin->id,
            'title' => 'Environmental Essay: Global Warming',
            'description' => 'Practice an essay response about climate change.',
            'type' => 'writing',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        $writingTask = WritingTask::create([
            'id' => $writingTaskId,
            'creator_id' => $admin->id,
            'title' => 'Environmental Essay: Global Warming',
            'description' => 'Practice an essay response about climate change.',
            'task_type' => 'essay',
            'prompt' => 'Global warming is a serious issue. What are the causes and solutions?',
            'word_limit' => 250,
            'is_public' => true,
            'is_published' => true,
        ]);

        WritingTaskQuestion::create([
            'id' => Str::uuid(),
            'writing_task_id' => $writingTask->id,
            'question_number' => 1,
            'question_type' => 'essay',
            'question_text' => 'Discuss the primary causes of global warming and suggest two effective solutions.',
            'word_limit' => 250,
        ]);

        // 5. SEED SPEAKING
        $speakingTaskId = (string) Str::uuid();

        Test::create([
            'id' => $speakingTaskId,
            'creator_id' => $admin->id,
            'title' => 'General English Interview',
            'description' => 'Mock interview about hobbies and daily life.',
            'type' => 'speaking',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        $speakingTask = SpeakingTask::create([
            'id' => $speakingTaskId,
            'created_by' => $admin->id,
            'title' => 'General English Interview',
            'description' => 'Mock interview about hobbies and daily life.',
            'difficulty_level' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        $sSection = SpeakingSection::create([
            'id' => Str::uuid(),
            'test_id' => $speakingTaskId,
            'section_type' => 'introduction',
            'description' => 'Basic introduction questions.',
            'prep_time_seconds' => 10,
        ]);

        $sTopic = SpeakingTopic::create([
            'id' => Str::uuid(),
            'speaking_section_id' => $sSection->id,
            'topic_name' => 'Your Hobbies',
        ]);

        SpeakingQuestion::create([
            'id' => Str::uuid(),
            'speaking_topic_id' => $sTopic->id,
            'question_number' => 1,
            'question_text' => 'What do you like to do in your free time?',
            'time_limit_seconds' => 60,
        ]);

        $this->command->info('DiscoverTestSeeder: Successfully seeded all 4 skill types with MODERN structures.');
    }
}
