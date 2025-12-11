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
        Schema::create('speaking_task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('test_id'); // the speaking test/task
            $table->uuid('class_id');
            $table->uuid('assigned_by'); // teacher who assigned
            $table->timestamp('due_date')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('allow_retake')->default(true);
            $table->integer('max_attempts')->default(3);
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('speaking_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('test_id'); // speaking task
            $table->uuid('student_id');
            $table->integer('attempt_number')->default(1);
            $table->enum('status', ['to_do', 'in_progress', 'submitted', 'reviewed', 'done'])->default('to_do');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('total_time_seconds')->nullable();
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['test_id', 'student_id', 'attempt_number']);
        });

        Schema::create('speaking_recordings', function (Blueprint $table) {
             $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('question_id');
            $table->string('audio_file_path', 500);
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('recording_started_at')->nullable();
            $table->timestamp('recording_ended_at')->nullable();
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('speaking_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('speaking_questions')->onDelete('cascade');
        });

        Schema::create('speaking_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('teacher_id');
            $table->integer('total_score')->nullable();
            $table->text('overall_feedback')->nullable();
            $table->json('question_scores')->nullable(); // structured scores per question
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('speaking_submissions')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('submission_id'); // One review per submission
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_reviews');
        Schema::dropIfExists('speaking_recordings');
        Schema::dropIfExists('speaking_submissions');
        Schema::dropIfExists('speaking_task_assignments');
    }
};
