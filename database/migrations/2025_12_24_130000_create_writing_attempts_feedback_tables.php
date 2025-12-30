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
        // Create writing attempts table (for retake functionality)
        Schema::create('writing_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('writing_task_id');
            $table->uuid('student_id');
            $table->integer('attempt_number')->default(1);
            $table->enum('attempt_type', [
                'first_attempt',
                'whole_essay', 
                'choose_questions',
                'specific_questions'
            ])->default('first_attempt');
            $table->json('selected_questions')->nullable(); // Question IDs for selective retake
            $table->enum('status', [
                'in_progress',
                'submitted', 
                'reviewed',
                'completed',
                'abandoned'
            ])->default('in_progress');
            $table->integer('time_taken_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('writing_task_id')->references('id')->on('writing_tasks')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['writing_task_id', 'student_id']);
            $table->index(['attempt_number']);
        });

        // Create writing feedback table (detailed scoring and feedback)
        Schema::create('writing_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('question_id')->nullable(); // Specific question feedback
            $table->enum('feedback_type', [
                'overall',
                'grammar',
                'content', 
                'structure',
                'vocabulary',
                'coherence'
            ])->default('overall');
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->text('comments')->nullable();
            $table->json('detailed_feedback')->nullable(); // Structured feedback data
            $table->json('suggestions')->nullable(); // Improvement suggestions
            $table->uuid('graded_by')->nullable(); // Teacher/grader ID
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('writing_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('writing_task_questions')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['submission_id', 'feedback_type']);
        });

        // Add missing columns to writing_submissions table
        Schema::table('writing_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('writing_submissions', 'attempt_id')) {
                $table->uuid('attempt_id')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('writing_submissions', 'question_id')) {
                $table->uuid('question_id')->nullable()->after('attempt_id');
            }

            // Add foreign keys if columns were added
            if (Schema::hasColumn('writing_submissions', 'attempt_id')) {
                $table->foreign('attempt_id')->references('id')->on('writing_attempts')->onDelete('cascade');
            }
            if (Schema::hasColumn('writing_submissions', 'question_id')) {
                $table->foreign('question_id')->references('id')->on('writing_task_questions')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        Schema::table('writing_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('writing_submissions', 'attempt_id')) {
                $table->dropForeign(['attempt_id']);
                $table->dropColumn('attempt_id');
            }
            if (Schema::hasColumn('writing_submissions', 'question_id')) {
                $table->dropForeign(['question_id']);
                $table->dropColumn('question_id');
            }
        });

        Schema::dropIfExists('writing_feedback');
        Schema::dropIfExists('writing_attempts');
    }
};