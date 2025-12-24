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
        // Add questions field to writing_tasks for JSON storage (like reading_tasks)
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->json('questions')->nullable(); // JSON array of questions like reading tasks
            
            // Add these fields only if they don't exist
            if (!Schema::hasColumn('writing_tasks', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('writing_tasks', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('writing_tasks', 'instructions')) {
                $table->text('instructions')->nullable();
            }
            
            // Add missing validation fields
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->integer('word_limit')->nullable(); // Overall task word limit
            $table->text('sample_answer')->nullable(); // Overall task sample
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_published')->default(false);
        });

        // Create separate writing task questions table (like test_questions)
        Schema::create('writing_task_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('writing_task_id');
            $table->enum('question_type', [
                'essay',
                'short_answer', 
                'creative_writing',
                'argumentative',
                'descriptive',
                'narrative',
                'summary',
                'letter',
                'report',
                'opinion',
                'comparison',
                'cause_effect',
                'problem_solution'
            ])->default('essay');
            $table->integer('question_number')->default(1);
            $table->text('question_text');
            $table->text('instructions')->nullable();
            $table->integer('word_limit')->nullable();
            $table->integer('min_word_count')->nullable();
            $table->integer('time_limit_seconds')->nullable(); // Per question time limit
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->text('rubric')->nullable(); // Grading criteria
            $table->text('sample_answer')->nullable();
            $table->json('question_data')->nullable(); // Additional question-specific data
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('writing_task_id')->references('id')->on('writing_tasks')->onDelete('cascade');
            $table->index(['writing_task_id']);
            $table->index(['question_type']);
        });

        // Create writing task question resources (for images, files, etc.)
        Schema::create('writing_task_question_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('writing_question_id');
            $table->enum('resource_type', ['image', 'audio', 'video', 'document'])->default('image');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('writing_question_id')->references('id')->on('writing_task_questions')->onDelete('cascade');
            $table->index(['writing_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_task_question_resources');
        Schema::dropIfExists('writing_task_questions');
        
        Schema::table('writing_tasks', function (Blueprint $table) {
            // Drop columns only if they exist
            $columnsToDrop = [];
            
            if (Schema::hasColumn('writing_tasks', 'questions')) {
                $columnsToDrop[] = 'questions';
            }
            if (Schema::hasColumn('writing_tasks', 'difficulty')) {
                $columnsToDrop[] = 'difficulty';
            }
            if (Schema::hasColumn('writing_tasks', 'word_limit')) {
                $columnsToDrop[] = 'word_limit';
            }
            if (Schema::hasColumn('writing_tasks', 'sample_answer')) {
                $columnsToDrop[] = 'sample_answer';
            }
            if (Schema::hasColumn('writing_tasks', 'due_date')) {
                $columnsToDrop[] = 'due_date';
            }
            if (Schema::hasColumn('writing_tasks', 'is_published')) {
                $columnsToDrop[] = 'is_published';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};