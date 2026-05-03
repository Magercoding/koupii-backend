<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\User;
use Illuminate\Support\Str;

class DiscoverListeningSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        // Cleanup
        Test::where('type', 'listening')->where('is_public', true)->delete();
        ListeningTask::where('is_public', true)->delete();

        $id = (string) Str::uuid();
        
        Test::create([
            'id' => $id,
            'creator_id' => $admin->id,
            'title' => 'Library Induction Section 1',
            'description' => 'Practice listening skills with library orientation.',
            'type' => 'listening',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        ListeningTask::create([
            'id' => $id,
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
            'listening_task_id' => $id,
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
    }
}
