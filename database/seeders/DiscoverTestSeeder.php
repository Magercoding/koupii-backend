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

        // Clear existing public tests to have a clean slate for fresh seeding
        Test::where('is_public', true)->delete();

        // 2. SEED READING (IELTS Style)
        $readingTest = Test::create([
            'id' => Str::uuid(),
            'creator_id' => $admin->id,
            'title' => 'Academic Reading: The Future of AI',
            'description' => 'A comprehensive IELTS-style reading test covering academic passages about Artificial Intelligence.',
            'type' => 'reading',
            'difficulty' => 'advanced',
            'test_type' => 'single',
            'is_public' => true,
            'is_published' => true,
            'timer_mode' => 'countdown',
            'timer_settings' => ['duration' => 1200], // 20 mins
        ]);

        $passage = Passage::create([
            'id' => Str::uuid(),
            'test_id' => $readingTest->id,
            'title' => 'The Evolution of Neural Networks',
            'description' => 'Academic passage about the history and future of AI.',
            'transcript' => [
                ['type' => 'text', 'content' => "The concept of neural networks dates back to the mid-20th century, inspired by the biological structures of the human brain. Early attempts, such as the Perceptron, laid the groundwork for what would eventually become the backbone of modern machine learning. However, it wasn't until the advent of deep learning and increased computational power that AI truly began to surpass human capabilities in specific domains.\n\nResearchers argue that the next frontier lies in General Artificial Intelligence (AGI), where machines can perform any intellectual task that a human can. While we are still decades away from achieving this, the ethical implications are already being debated in academic and political circles. Issues of bias, transparency, and the displacement of human labor are at the forefront of the conversation."]
            ]
        ]);

        $qGroupR = QuestionGroup::create([
            'id' => Str::uuid(),
            'passage_id' => $passage->id,
            'instruction' => 'Choose the correct option for each question based on the passage above.',
        ]);

        $qr1 = TestQuestion::create([
            'id' => Str::uuid(),
            'question_group_id' => $qGroupR->id,
            'question_type' => 'multiple_choice',
            'question_number' => 1,
            'question_text' => 'What was the primary inspiration for early neural networks?',
            'correct_answers' => ['B'],
            'points_value' => 1,
        ]);

        QuestionOption::create(['id' => Str::uuid(), 'question_id' => $qr1->id, 'option_key' => 'A', 'option_text' => 'Clockwork mechanisms']);
        QuestionOption::create(['id' => Str::uuid(), 'question_id' => $qr1->id, 'option_key' => 'B', 'option_text' => 'Biological brain structures']);

        // 3. SEED LISTENING
        $listeningTest = Test::create([
            'id' => Str::uuid(),
            'creator_id' => $admin->id,
            'title' => 'Listening: Library Orientation',
            'description' => 'Practice your listening skills with a realistic library orientation scenario.',
            'type' => 'listening',
            'difficulty' => 'intermediate',
            'test_type' => 'single',
            'is_public' => true,
            'is_published' => true,
            'timer_mode' => 'none',
        ]);

        $listeningTask = ListeningTask::create([
            'id' => Str::uuid(),
            'test_id' => $listeningTest->id,
            'title' => 'Library Induction Section 1',
            'task_type' => 'conversation',
            'difficulty' => 'intermediate',
            'is_published' => true,
            'created_by' => $admin->id,
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', // Sample audio
            'instructions' => 'Listen to the conversation between a librarian and a new student.',
        ]);

        ListeningQuestion::create([
            'id' => Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'short_answer',
            'question_text' => 'What is the maximum number of books a student can borrow?',
            'correct_answers' => ['8', 'eight'],
            'points' => 1,
            'order_index' => 1,
        ]);

        // 4. SEED WRITING
        $writingTest = Test::create([
            'id' => Str::uuid(),
            'creator_id' => $admin->id,
            'title' => 'Writing: Environmental Issues',
            'description' => 'Practice an essay response to a common environmental topic.',
            'type' => 'writing',
            'difficulty' => 'advanced',
            'test_type' => 'single',
            'is_public' => true,
            'is_published' => true,
        ]);

        $writingTask = WritingTask::create([
            'id' => Str::uuid(),
            'test_id' => $writingTest->id,
            'creator_id' => $admin->id,
            'title' => 'Essay: Global Warming',
            'task_type' => 'essay',
            'prompt' => 'Global warming is a serious issue. What are the causes and solutions?',
            'word_limit' => 250,
            'is_published' => true,
        ]);

        WritingTaskQuestion::create([
            'id' => Str::uuid(),
            'writing_task_id' => $writingTask->id,
            'question_type' => 'essay',
            'question_number' => 1,
            'question_text' => 'Write about global warming.',
            'word_limit' => 250,
        ]);

        // 5. SEED SPEAKING
        $speakingTest = Test::create([
            'id' => Str::uuid(),
            'creator_id' => $admin->id,
            'title' => 'Speaking: Daily Life',
            'description' => 'A mock speaking test covering personal topics and hobbies.',
            'type' => 'speaking',
            'difficulty' => 'intermediate',
            'test_type' => 'single',
            'is_public' => true,
            'is_published' => true,
        ]);

        $speakingTask = SpeakingTask::create([
            'id' => Str::uuid(),
            'test_id' => $speakingTest->id,
            'created_by' => $admin->id,
            'title' => 'Mock Interview',
            'difficulty_level' => 'intermediate',
            'is_published' => true,
        ]);

        $sSection = SpeakingSection::create([
            'id' => Str::uuid(),
            'test_id' => $speakingTest->id,
            'section_type' => 'introduction',
            'description' => 'General introductory questions.',
            'prep_time_seconds' => 0,
        ]);

        $sTopic = SpeakingTopic::create([
            'id' => Str::uuid(),
            'speaking_section_id' => $sSection->id,
            'topic_name' => 'Hobbies',
        ]);

        SpeakingQuestion::create([
            'id' => Str::uuid(),
            'speaking_topic_id' => $sTopic->id,
            'question_number' => 1,
            'question_text' => 'What do you like to do in your free time?',
            'time_limit_seconds' => 60,
        ]);

        $this->command->info('DiscoverTestSeeder: Successfully seeded all 4 skill types for public tests.');
    }
}
