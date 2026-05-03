<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;
use App\Models\SpeakingTask;
use App\Models\User;
use Illuminate\Support\Str;

class DiscoverSpeakingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        // Cleanup
        Test::where('type', 'speaking')->where('is_public', true)->delete();
        SpeakingTask::where('is_public', true)->delete();

        $id = (string) Str::uuid();
        
        Test::create([
            'id' => $id,
            'creator_id' => $admin->id,
            'title' => 'Daily English Interview',
            'description' => 'General English conversation practice for beginners.',
            'type' => 'speaking',
            'difficulty' => 'intermediate',
            'is_public' => true,
            'is_published' => true,
        ]);

        SpeakingTask::create([
            'id' => $id,
            'created_by' => $admin->id,
            'title' => 'Daily English Interview',
            'description' => 'General English conversation practice for beginners.',
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
    }
}
