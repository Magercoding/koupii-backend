<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\SpeakingTask;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use Carbon\Carbon;

class TestScenarioSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing test users created by UserSeeder
        $teacher = User::where('email', 'teacher@koupii.com')->first();
        $student1 = User::where('email', 'student1@koupii.com')->first();
        $student2 = User::where('email', 'student2@koupii.com')->first();

        if (!$teacher || !$student1 || !$student2) {
            $this->command->error('Users not found. Please run UserSeeder first.');
            return;
        }

        $now = Carbon::now();

        // Create a Class
        $classId = (string) Str::uuid();
        Classes::create([
            'id' => $classId,
            'teacher_id' => $teacher->id,
            'name' => 'English 101 - Test Class',
            'description' => 'A class for testing Phase 3 integrations',
            'class_code' => 'TEST101',
            'is_active' => true,
        ]);

        // Enroll students
        ClassEnrollment::create([
            'id' => (string) Str::uuid(),
            'class_id' => $classId,
            'student_id' => $student1->id,
            'status' => 'active',
            'enrolled_at' => $now,
        ]);

        ClassEnrollment::create([
            'id' => (string) Str::uuid(),
            'class_id' => $classId,
            'student_id' => $student2->id,
            'status' => 'active',
            'enrolled_at' => $now,
        ]);

        // Create a Listening Task
        $listeningTaskId = (string) Str::uuid();
        ListeningTask::create([
            'id' => $listeningTaskId,
            'task_type' => 'listening_task',
            'title' => 'Test Listening Task 1',
            'description' => 'This is a test listening task for Phase 3',
            'instructions' => 'Listen carefully and answer the questions.',
            'difficulty' => 'intermediate',
            'timer_type' => 'none',
            'allow_retake' => true,
            'max_retake_attempts' => 3,
            'is_published' => true,
            'created_by' => $teacher->id,
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', // dummy audio
            'audio_duration_seconds' => 120,
            'show_transcript' => true,
            'allow_replay' => true,
            'difficulty_level' => 'intermediate',
        ]);

        // Create Listening Questions
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTaskId,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is the main topic of the audio?',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answers' => ['Option A'],
            'points' => 10,
            'order_index' => 1,
        ]);
        
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTaskId,
            'question_type' => 'short_answer',
            'question_text' => 'Type what you hear.',
            'options' => [],
            'correct_answers' => ['hello world'],
            'points' => 10,
            'order_index' => 2,
        ]);

        // Create a Speaking Task
        $speakingTaskId = (string) Str::uuid();
        SpeakingTask::create([
            'id' => $speakingTaskId,
            'title' => 'Test Speaking Task 1',
            'description' => 'This is a test speaking task for Phase 3',
            'instructions' => 'Read the prompt and record your answer.',
            'difficulty_level' => 'intermediate',
            'time_limit_seconds' => 60,
            'topic' => 'General Topic',
            'situation_context' => 'You are introducing yourself.',
            'questions' => [
                ['text' => 'Please introduce yourself and your hobbies.']
            ],
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        // Create Assignments
        $listeningAssignmentId = (string) Str::uuid();
        Assignment::create([
            'id' => $listeningAssignmentId,
            'class_id' => $classId,
            'task_id' => $listeningTaskId,
            'task_type' => 'listening_task',
            'assigned_by' => $teacher->id,
            'title' => 'Listening Practice 1',
            'due_date' => clone $now->addDays(7),
            'is_published' => true,
            'max_attempts' => 3,
            'status' => 'active',
            'source_type' => 'manual',
            'type' => 'listening_task',
        ]);

        $speakingAssignmentId = (string) Str::uuid();
        Assignment::create([
            'id' => $speakingAssignmentId,
            'class_id' => $classId,
            'task_id' => $speakingTaskId,
            'task_type' => 'speaking_task',
            'assigned_by' => $teacher->id,
            'title' => 'Speaking Practice 1',
            'due_date' => clone $now->addDays(7),
            'is_published' => true,
            'max_attempts' => 3,
            'status' => 'active',
            'source_type' => 'manual',
            'type' => 'speaking_task',
        ]);

        // Assign to student 1 & 2
        foreach ([$student1, $student2] as $student) {
            StudentAssignment::create([
                'id' => (string) Str::uuid(),
                'assignment_id' => $listeningAssignmentId,
                'student_id' => $student->id,
                'assignment_type' => 'listening_task',
                'status' => 'not_started',
                'attempt_number' => 0,
                'attempt_count' => 0,
            ]);

            StudentAssignment::create([
                'id' => (string) Str::uuid(),
                'assignment_id' => $speakingAssignmentId,
                'student_id' => $student->id,
                'assignment_type' => 'speaking_task',
                'status' => 'not_started',
                'attempt_number' => 0,
                'attempt_count' => 0,
            ]);
        }

        $this->command->info('Test Scenario seeded successfully!');
    }
}
