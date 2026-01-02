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
        Schema::create('reading_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('test_id')->nullable(); // Link to test if part of a test
            $table->string('task_type')->default('reading'); // reading, comprehension, etc.
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('difficulty', ['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced', 'proficiency'])->nullable();
            $table->enum('timer_type', ['none', 'countdown', 'countup'])->default('none');
            $table->integer('time_limit_seconds')->nullable();
            $table->boolean('allow_retake')->default(false);
            $table->integer('max_retake_attempts')->nullable();
            $table->json('retake_options')->nullable(); // ["repeat_all", "focus_mistakes", "choose_questions"]
            $table->boolean('allow_submission_files')->default(false);
            $table->boolean('is_published')->default(false);
            $table->uuid('created_by')->nullable();
            $table->json('passages')->nullable(); // Array of passage data with question groups
            $table->json('passage_images')->nullable(); // Array of uploaded images
            $table->integer('suggest_time_minutes')->nullable();
            $table->string('difficulty_level')->nullable(); // beginner, intermediate, advanced, etc.
            $table->json('question_types')->nullable(); // Array of question types in this task
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('set null');
            $table->index(['task_type']);
            $table->index(['difficulty']);
            $table->index(['is_published']);
            $table->index(['created_by']);
        });

        // Reading task assignments table
        Schema::create('reading_task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reading_task_id');
            $table->uuid('classroom_id')->nullable();
            $table->uuid('assigned_by')->nullable(); // teacher id
            $table->timestamp('due_date')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('reading_task_id')->references('id')->on('reading_tasks')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_task_assignments');
        Schema::dropIfExists('reading_tasks');
    }
};
