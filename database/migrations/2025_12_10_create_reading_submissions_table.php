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
        // Reading test submissions by students
        Schema::create('reading_submissions', function (Blueprint $table) {
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
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['test_id', 'student_id', 'attempt_number']);
        });

        // Student answers for each question
        Schema::create('reading_question_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('question_id');
            $table->json('student_answer')->nullable(); // Store the actual answer
            $table->json('correct_answer')->nullable(); // Store correct answer for comparison
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->integer('time_spent_seconds')->nullable();
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('reading_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('test_questions')->onDelete('cascade');
            $table->unique(['submission_id', 'question_id']);
        });

        // Highlight segments for show explanation feature
        Schema::create('highlight_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->text('highlighted_text'); // The text that should be highlighted
            $table->integer('start_position')->nullable(); // Start position in passage
            $table->integer('end_position')->nullable(); // End position in passage
            $table->text('explanation')->nullable(); // Explanation for this highlight
            $table->string('highlight_color')->default('#ffff00'); // Yellow default
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('test_questions')->onDelete('cascade');
        });

        // Student vocabulary discoveries from reading tests
        Schema::create('student_vocabulary_discoveries', function (Blueprint $table) {
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
            $table->unique(['student_id', 'test_id', 'vocabulary_id']);
        });

        // Student personal vocabulary bank
        Schema::create('student_vocabulary_bank', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('vocabulary_id');
            $table->uuid('discovered_from_test_id')->nullable(); // Which test they discovered this from
            $table->boolean('is_mastered')->default(false);
            $table->integer('practice_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('vocabularies')->onDelete('cascade');
            $table->foreign('discovered_from_test_id')->references('id')->on('tests')->nullOnDelete();
            $table->unique(['student_id', 'vocabulary_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_vocabulary_bank');
        Schema::dropIfExists('student_vocabulary_discoveries');
        Schema::dropIfExists('highlight_segments');
        Schema::dropIfExists('reading_question_answers');
        Schema::dropIfExists('reading_submissions');
    }
};