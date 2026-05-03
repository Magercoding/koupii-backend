<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\WritingTask;
use App\Models\WritingTaskQuestion;
use App\Models\User;
use Illuminate\Support\Str;

class DiscoverWritingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        // Cleanup
        Test::where('type', 'writing')->where('is_public', true)->delete();
        WritingTask::where('is_public', true)->delete();

        $id = (string) Str::uuid();
        
        Test::create([
            'id' => $id,
            'creator_id' => $admin->id,
            'title' => 'Essay: Climate Change',
            'description' => 'Academic writing task about global warming.',
            'type' => 'writing',
            'difficulty' => 'advanced',
            'is_public' => true,
            'is_published' => true,
        ]);

        WritingTask::create([
            'id' => $id,
            'creator_id' => $admin->id,
            'title' => 'Essay: Climate Change',
            'description' => 'Academic writing task about global warming.',
            'task_type' => 'essay',
            'is_public' => true,
            'is_published' => true,
            'prompt' => 'Global warming causes and solutions.',
            'passages' => [
                [
                    'title' => 'Global Warming Task',
                    'description' => 'Write a 250-word essay about the impacts of global warming.',
                    'questions' => [
                        [
                            'question_number' => 1,
                            'question_text' => 'Discuss the primary causes of global warming.'
                        ]
                    ]
                ]
            ],
            'questions' => [
                [
                    'question_number' => 1,
                    'question_text' => 'Discuss the primary causes of global warming.',
                    'question_type' => 'essay'
                ]
            ]
        ]);

        WritingTaskQuestion::create([
            'id' => Str::uuid(),
            'writing_task_id' => $id,
            'question_number' => 1,
            'question_type' => 'essay',
            'question_text' => 'Discuss the primary causes of global warming.',
        ]);
    }
}
