<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Models\ReadingTask;
use App\Models\ListeningTask;
use App\Models\ListeningQuestion;
use App\Models\SpeakingTask;
use App\Models\WritingTask;
use App\Models\WritingTaskQuestion;
use Carbon\Carbon;

class MissionSeeder extends Seeder
{
    public function run(): void
    {
        // Cleanup existing test data to avoid UUID conflicts
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('student_assignments')->truncate();
        \DB::table('assignments')->truncate();
        \DB::table('class_enrollments')->truncate();
        \DB::table('classes')->truncate();
        \DB::table('writing_task_questions')->truncate();
        \DB::table('writing_tasks')->truncate();
        \DB::table('speaking_tasks')->truncate();
        \DB::table('listening_questions')->truncate();
        \DB::table('listening_tasks')->truncate();
        \DB::table('reading_tasks')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get existing test users
        $teacher = User::where('email', 'teacher@koupii.com')->first();
        $student1 = User::where('email', 'student1@koupii.com')->first();
        $student2 = User::where('email', 'student2@koupii.com')->first();

        if (!$teacher || !$student1 || !$student2) {
            $this->command->error('Test users not found. Please run UserSeeder first.');
            return;
        }

        $now = Carbon::now();

        // 1. Create the IELTS Preparation Class
        $classId = '9d1b8d30-be6d-4cc6-abca-1a6d2bbfd07d'; // Fixed UUID for testing consistency
        $class = Classes::updateOrCreate(
            ['name' => 'IELTS Preparation - Master Class'],
            [
                'id' => $classId,
                'teacher_id' => $teacher->id,
                'description' => 'Comprehensive IELTS preparation covering all four skills.',
                'class_code' => 'IELTS-MASTER',
                'is_active' => true,
            ]
        );

        // 2. Enroll Students
        foreach ([$student1, $student2] as $student) {
            ClassEnrollment::updateOrCreate(
                ['class_id' => $class->id, 'student_id' => $student->id],
                [
                    'id' => (string) Str::uuid(),
                    'status' => 'active',
                    'enrolled_at' => $now,
                ]
            );
        }

        // --- SEED MISSIONS ---
        
        // A. READING TASK
        $readingTask = ReadingTask::create([
            'id' => (string) Str::uuid(),
            'title' => 'The Impact of AI on Modern Workplaces',
            'description' => 'A reading comprehension about how AI is changing the way we work.',
            'instructions' => 'Read the passage carefully and answer the multiple-choice questions.',
            'difficulty' => 'intermediate',
            'timer_type' => 'countdown',
            'time_limit_seconds' => 900, // 15 mins
            'allow_retake' => true,
            'max_retake_attempts' => 3,
            'is_published' => true,
            'created_by' => $teacher->id,
            'task_type' => 'reading_task',
            'passages' => [
                [
                    'title' => 'The Rise of Automation',
                    'content' => 'Artificial intelligence and automation are no longer futuristic concepts; they are actively reshaping modern industries. From manufacturing to data analysis, AI systems can process information at speeds no human can match. However, this shift raises questions about the future role of human creativity and empathy...',
                    'question_groups' => [
                        [
                            'type' => 'multiple_choice',
                            'instruction' => 'Choose the correct letter, A, B, C or D.',
                            'questions' => [
                                [
                                    'id' => "q1-1",
                                    'question_number' => 1,
                                    'question_text' => 'What is the primary benefit of AI mentioned in the passage?',
                                    'question_type' => 'multiple_choice',
                                    'options' => [
                                        ['id' => 'A', 'text' => 'Human creativity'],
                                        ['id' => 'B', 'text' => 'Processing speed'],
                                        ['id' => 'C', 'text' => 'Industrial manufacturing'],
                                        ['id' => 'D', 'text' => 'Futuristic concepts']
                                    ],
                                    'correct_answers' => 'B'
                                ],
                                [
                                    'id' => "q1-2",
                                    'question_number' => 2,
                                    'question_text' => 'Which human traits are considered potentially irreplaceable by AI?',
                                    'question_type' => 'multiple_choice',
                                    'options' => [
                                        ['id' => 'A', 'text' => 'Data analysis'],
                                        ['id' => 'B', 'text' => 'Manufacturing speed'],
                                        ['id' => 'C', 'text' => 'Empathy and creativity'],
                                        ['id' => 'D', 'text' => 'Automation']
                                    ],
                                    'correct_answers' => 'C'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Sustainable Urban Planning',
                    'content' => 'As the global population becomes increasingly urbanized, the challenge of creating sustainable cities has taken center stage. Urban planners are now focusing on "green infrastructure"—integrating natural systems like parks, wetlands, and vertical gardens into the city fabric to manage rainwater, reduce heat, and improve air quality...',
                    'question_groups' => [
                        [
                            'type' => 'multiple_choice',
                            'instruction' => 'Choose the correct letter, A, B, C or D.',
                            'questions' => [
                                [
                                    'id' => "q2-3",
                                    'question_number' => 3,
                                    'question_text' => 'What is the main goal of "green infrastructure" according to the passage?',
                                    'question_type' => 'multiple_choice',
                                    'options' => [
                                        ['id' => 'A', 'text' => 'Increasing city population'],
                                        ['id' => 'B', 'text' => 'Managing natural systems in cities'],
                                        ['id' => 'C', 'text' => 'Building more skyscrapers'],
                                        ['id' => 'D', 'text' => 'Reducing the number of parks']
                                    ],
                                    'correct_answers' => 'B'
                                ],
                                [
                                    'id' => "q2-4",
                                    'question_number' => 4,
                                    'question_text' => 'Which of the following is NOT mentioned as a benefit of green infrastructure?',
                                    'question_type' => 'multiple_choice',
                                    'options' => [
                                        ['id' => 'A', 'text' => 'Rainwater management'],
                                        ['id' => 'B', 'text' => 'Temperature reduction'],
                                        ['id' => 'C', 'text' => 'Improved air quality'],
                                        ['id' => 'D', 'text' => 'Cheaper housing prices']
                                    ],
                                    'correct_answers' => 'D'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // B. LISTENING TASK
        $listeningTask = ListeningTask::create([
            'id' => (string) Str::uuid(),
            'title' => 'Environmental Challenges in Urban Areas',
            'description' => 'Listen to a lecture about pollution in cities.',
            'instructions' => 'Listen to the audio and answer the questions that follow. You can listen twice.',
            'difficulty' => 'advanced',
            'is_published' => true,
            'created_by' => $teacher->id,
            'task_type' => 'listening_task',
            'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
            'audio_duration_seconds' => 180,
            'show_transcript' => true,
            'allow_replay' => true,
            'difficulty_level' => 'advanced',
            'max_retake_attempts' => 3,
            'allow_retake' => true,
        ]);

        // Q1: Multiple Choice
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is the main source of urban air pollution discussed?',
            'options' => ['Factories', 'Transportation', 'Domestic heating', 'Construction'],
            'correct_answers' => ['Transportation'],
            'points' => 10,
            'order_index' => 1,
        ]);

        // Q2: Multiple Choice
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'According to the lecture, which city implemented a successful bike-sharing program?',
            'options' => ['New York', 'London', 'Amsterdam', 'Copenhagen'],
            'correct_answers' => ['Copenhagen'],
            'points' => 10,
            'order_index' => 2,
        ]);

        // Q3: Multiple Answer (Select 2)
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'multiple_answer',
            'question_text' => 'Which TWO factors are contributing to the rise in city temperatures?',
            'options' => ['Glass buildings', 'Lack of green spaces', 'Electric vehicles', 'Paved surfaces', 'High population density'],
            'correct_answers' => ['Glass buildings', 'Paved surfaces'],
            'points' => 20,
            'order_index' => 3,
        ]);

        // Q4: Gap Fill / Sentence Completion
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'sentence_completion',
            'question_text' => 'The lecturer suggests that urban planning should focus more on ________ infrastructure.',
            'options' => null,
            'correct_answers' => ['sustainable', 'green'],
            'points' => 15,
            'order_index' => 4,
        ]);

        // Q5: Form Completion
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'form_completion',
            'question_text' => 'Name of the project: ________',
            'correct_answers' => ['Eco-City'],
            'points' => 10,
            'order_index' => 5,
        ]);

        // Q6: Table Completion
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'table_completion',
            'question_text' => 'Station: ________ | Line: Blue',
            'correct_answers' => ['North'],
            'points' => 10,
            'order_index' => 6,
        ]);

        // Q7: Map Labeling
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'map_labeling',
            'question_text' => 'The library is located at position ________ on the map.',
            'correct_answers' => ['B'],
            'points' => 10,
            'order_index' => 7,
        ]);

        // Q8: Short Answer
        ListeningQuestion::create([
            'id' => (string) Str::uuid(),
            'listening_task_id' => $listeningTask->id,
            'question_type' => 'short_answer',
            'question_text' => 'What is the minimum number of trees required per hectare?',
            'correct_answers' => ['50'],
            'points' => 10,
            'order_index' => 8,
        ]);

        // C. SPEAKING TASK
        $speakingTask = SpeakingTask::create([
            'id' => (string) Str::uuid(),
            'title' => 'Hometown Landmarks',
            'description' => 'Speak about a historical building or site in your city.',
            'instructions' => 'You have 1 minute to prepare and 2 minutes to speak.',
            'difficulty_level' => 'intermediate',
            'time_limit_seconds' => 120, // 2 mins
            'topic' => 'History and Culture',
            'situation_context' => 'You are giving a short presentation to tourists.',
            'questions' => [
                ['text' => 'Describe a historical building in your hometown. Where is it? Why is it important?']
            ],
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        // D. WRITING TASK
        $writingTask = WritingTask::create([
            'id' => (string) Str::uuid(),
            'title' => 'Public Transportation vs Roads',
            'description' => 'IELTS Writing Task 2 Essay prompt.',
            'instructions' => 'Write at least 250 words on the following topic.',
            'difficulty' => 'advanced',
            'task_type' => 'essay',
            'prompt' => 'Governments should invest more in public transportation than in roads. To what extent do you agree or disagree?',
            'min_word_count' => 250,
            'suggest_time_minutes' => 40,
            'is_published' => true,
            'creator_id' => $teacher->id,
            'word_limit' => 500,
            'max_retake_attempts' => 2,
        ]);

        // Create Writing Question
        WritingTaskQuestion::create([
            'id' => (string) Str::uuid(),
            'writing_task_id' => $writingTask->id,
            'question_type' => 'essay',
            'question_number' => 1,
            'question_text' => 'Governments should invest more in public transportation than in roads. To what extent do you agree or disagree?',
            'min_word_count' => 250,
        ]);

        // 3. Create Assignments and Link to Students
        $taskDefns = [
            ['model' => $readingTask, 'type' => 'reading_task'],
            ['model' => $listeningTask, 'type' => 'listening_task'],
            ['model' => $speakingTask, 'type' => 'speaking_task'],
            ['model' => $writingTask, 'type' => 'writing_task'],
        ];

        foreach ($taskDefns as $taskInfo) {
            $task = $taskInfo['model'];
            $type = $taskInfo['type'];

            $assignment = Assignment::create([
                'id' => (string) Str::uuid(),
                'class_id' => $classId,
                'task_id' => $task->id,
                'task_type' => $type,
                'assigned_by' => $teacher->id,
                'title' => $task->title,
                'due_date' => $now->copy()->addDays(7),
                'is_published' => true,
                'max_attempts' => 3,
                'status' => 'active',
                'source_type' => 'manual',
                'type' => $type,
            ]);

            // Link to Students
            foreach ([$student1, $student2] as $student) {
                StudentAssignment::create([
                    'id' => (string) Str::uuid(),
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'assignment_type' => $type,
                    'status' => 'not_started',
                    'attempt_number' => 0,
                    'attempt_count' => 0,
                ]);
            }
        }

        $this->command->info('Mission Seeder completed: 4 Skills seeded in IELTS-MASTER class.');
    }
}
