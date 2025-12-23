<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listening_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('task_type')->default('conversation'); // conversation, monologue, lecture, etc.
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // Writing pattern
            $table->enum('difficulty', ['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced', 'proficiency'])->nullable(); // Writing pattern
            $table->enum('timer_type', ['none', 'countdown', 'countup'])->default('none'); // Writing pattern
            $table->integer('time_limit_seconds')->nullable(); // Writing pattern
            $table->boolean('allow_retake')->default(false); // Writing pattern
            $table->integer('max_retake_attempts')->nullable(); // Writing pattern
            $table->json('retake_options')->nullable(); // Writing pattern: ["repeat_all", "focus_mistakes", "choose_questions"]
            $table->boolean('allow_submission_files')->default(false); // Writing pattern
            $table->boolean('is_published')->default(false); // Writing pattern
            $table->uuid('created_by')->nullable(); // Writing pattern
            $table->string('audio_url')->nullable();
            $table->integer('audio_duration_seconds')->nullable();
            $table->longText('transcript')->nullable();
            $table->json('audio_segments')->nullable(); // Array of segment data
            $table->integer('suggest_time_minutes')->nullable();
            $table->integer('max_attempts_per_audio')->default(3);
            $table->boolean('show_transcript')->default(false);
            $table->boolean('allow_replay')->default(true);
            $table->json('replay_settings')->nullable(); // Settings for replay functionality
            $table->string('difficulty_level')->nullable(); // beginner, intermediate, advanced, etc.
            $table->json('question_types')->nullable(); // Array of question types in this task
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null'); // Writing pattern
            $table->index(['task_type']);
            $table->index(['difficulty']);
            $table->index(['is_published']);
        });

        // Listening task question types mapping
        Schema::create('listening_task_question_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listening_task_id');
            $table->string('question_type_code'); // QT1, QT2, etc.
            $table->string('question_type_name'); // multiple_choice, multiple_answer, etc.
            $table->integer('question_count')->default(0);
            $table->integer('total_points')->default(0);
            $table->json('type_specific_settings')->nullable(); // Settings specific to this question type
            $table->timestamps();

            $table->foreign('listening_task_id')->references('id')->on('listening_tasks')->onDelete('cascade');
            $table->unique(['listening_task_id', 'question_type_code'], 'listening_task_qt_unique');
        });

        // Enhanced listening audio segments
        Schema::create('listening_task_audio_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listening_task_id');
            $table->string('segment_name'); // Part 1, Part 2, etc.
            $table->string('audio_url');
            $table->decimal('start_time', 8, 2)->default(0); // Start time in seconds
            $table->decimal('end_time', 8, 2); // End time in seconds
            $table->decimal('duration', 8, 2); // Duration in seconds
            $table->text('transcript')->nullable();
            $table->text('description')->nullable();
            $table->string('speaker')->nullable(); // Speaker information
            $table->string('accent')->nullable(); // British, American, Australian, etc.
            $table->decimal('speed_wpm', 5, 1)->nullable(); // Words per minute
            $table->json('keywords')->nullable(); // Important vocabulary
            $table->integer('difficulty_rating')->nullable(); // 1-5 rating
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('listening_task_id')->references('id')->on('listening_tasks')->onDelete('cascade');
            $table->index(['listening_task_id', 'start_time']);
        });

        // Create listening task assignments table (following writing pattern)
        Schema::create('listening_task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listening_task_id');
            $table->uuid('class_id')->nullable();
            $table->uuid('assigned_by')->nullable(); // teacher id
            $table->timestamp('due_date')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('listening_task_id')->references('id')->on('listening_tasks')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create listening questions table (15 question types support)
        Schema::create('listening_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listening_task_id');
            $table->string('question_type'); // multiple_choice, multiple_answer, matching, table_completion, etc.
            $table->text('question_text');
            $table->json('options')->nullable(); // for multiple choice, matching options, etc.
            $table->json('correct_answers'); // array of correct answers
            $table->integer('points')->default(1);
            $table->integer('order_index')->default(0);
            $table->decimal('start_time', 8, 2)->nullable(); // when in audio this question relates to
            $table->decimal('end_time', 8, 2)->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->foreign('listening_task_id')->references('id')->on('listening_tasks')->onDelete('cascade');
            $table->index(['listening_task_id', 'order_index']);
        });

        // Listening task performance analytics
        Schema::create('listening_task_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('listening_task_id');
            $table->uuid('student_id')->nullable();
            $table->string('analytics_type'); // task_completion, audio_interaction, question_performance
            $table->json('analytics_data');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('listening_task_id')->references('id')->on('listening_tasks')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['listening_task_id', 'analytics_type']);
            $table->index(['student_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listening_task_analytics');
        Schema::dropIfExists('listening_task_audio_segments');
        Schema::dropIfExists('listening_task_question_types');
        Schema::dropIfExists('listening_questions');
        Schema::dropIfExists('listening_task_assignments');
        Schema::dropIfExists('listening_tasks');
    }
};