<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\ListeningSubmission;
use App\Models\ReadingSubmission;
use App\Models\SpeakingSubmission;
use App\Models\Test;
use App\Models\User;
use App\Models\WritingSubmission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for clear seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ReadingSubmission::truncate();
        ListeningSubmission::truncate();
        WritingSubmission::truncate();
        SpeakingSubmission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $class = Classes::first();
        if (!$class) {
            $this->command->error('No class found to seed analytics.');
            return;
        }

        $students = User::where('role', 'student')->limit(10)->get();
        $tests = Test::all()->groupBy('type');

        $this->command->info('Seeding submissions for class: ' . $class->name);

        foreach ($students as $student) {
            // Seed Reading
            if (isset($tests['reading'])) {
                foreach ($tests['reading'] as $test) {
                    try {
                        ReadingSubmission::create([
                            'test_id' => $test->id,
                            'student_id' => $student->id,
                            'attempt_number' => 1,
                            'status' => 'completed',
                            'started_at' => Carbon::now()->subDays(rand(10, 30)),
                            'submitted_at' => Carbon::now()->subDays(rand(0, 9)),
                            'percentage' => rand(50, 95),
                            'total_score' => rand(10, 40),
                            'total_correct' => rand(15, 30),
                            'total_incorrect' => rand(0, 10),
                        ]);
                    } catch (\Exception $e) {
                        // Skip
                    }
                }
            }

            // Seed Listening
            if (isset($tests['listening'])) {
                foreach ($tests['listening'] as $test) {
                    $task = $test->listeningTasks()->first();
                    if ($task) {
                        try {
                            ListeningSubmission::create([
                                'listening_task_id' => $task->id,
                                'student_id' => $student->id,
                                'attempt_number' => 1,
                                'status' => 'completed',
                                'started_at' => Carbon::now()->subDays(rand(10, 30)),
                                'submitted_at' => Carbon::now()->subDays(rand(0, 9)),
                                'percentage' => rand(40, 90),
                                'total_correct' => rand(20, 40),
                            ]);
                        } catch (\Exception $e) {
                            // Skip
                        }
                    }
                }
            }
        }

        $this->command->info('Analytics data seeded successfully!');
    }
}
