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
        // Listening test submissions by students
        Schema::create('listening_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('test_id');
            $table->uuid('student_id');
            $table->integer('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'submitted', 'completed'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('time_taken_seconds')->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->integer('total_correct')->default(0);
            $table->integer('total_incorrect')->default(0);
            $table->integer('total_unanswered')->default(0);
            $table->json('audio_play_counts')->nullable(); // Track how many times audio was played
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['test_id', 'student_id', 'attempt_number']);
        });

        // Student answers for each listening question
        Schema::create('listening_question_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('question_id');
            $table->json('student_answer')->nullable(); // Store the actual answer
            $table->json('correct_answer')->nullable(); // Store correct answer for comparison
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->integer('time_spent_seconds')->nullable();
            $table->integer('audio_play_count')->default(0); // How many times student played audio for this question
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('listening_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('test_questions')->onDelete('cascade');
            $table->unique(['submission_id', 'question_id']);
        });

        // Audio segments for listening explanations
        Schema::create('listening_audio_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->string('audio_file_path')->nullable(); // Specific audio segment for explanation
            $table->decimal('start_time', 8, 2)->nullable(); // Start time in seconds
            $table->decimal('end_time', 8, 2)->nullable(); // End time in seconds
            $table->text('transcript_text')->nullable(); // Text transcript for this segment
            $table->text('explanation')->nullable(); // Explanation for this audio segment
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('test_questions')->onDelete('cascade');
        });

        // Student vocabulary discoveries from listening tests
        Schema::create('listening_vocabulary_discoveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('test_id');
            $table->uuid('vocabulary_id');
            $table->timestamp('discovered_at');
            $table->boolean('is_saved')->default(false); // Whether student saved it to their vocab bank
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('vocabularies')->onDelete('cascade');
            $table->unique(['student_id', 'test_id', 'vocabulary_id'], 'listening_vocab_discovery_unique');
        });

        // Audio playback logs for analytics
        Schema::create('listening_audio_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('passage_id')->nullable(); // Which audio passage was played
            $table->uuid('question_id')->nullable(); // Which question audio was played
            $table->timestamp('played_at');
            $table->decimal('start_position', 8, 2)->default(0); // Start position in audio
            $table->decimal('end_position', 8, 2)->nullable(); // End position in audio
            $table->integer('duration_played')->nullable(); // How long was played in seconds
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('listening_submissions')->onDelete('cascade');
            $table->foreign('passage_id')->references('id')->on('passages')->nullOnDelete();
            $table->foreign('question_id')->references('id')->on('test_questions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listening_audio_logs');
        Schema::dropIfExists('listening_vocabulary_discoveries');
        Schema::dropIfExists('listening_audio_segments');
        Schema::dropIfExists('listening_question_answers');
        Schema::dropIfExists('listening_submissions');
    }
};