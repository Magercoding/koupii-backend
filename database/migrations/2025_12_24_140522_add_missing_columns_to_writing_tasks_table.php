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
        Schema::table('writing_tasks', function (Blueprint $table) {
            // Add missing columns that the WritingTask model expects (check existence first)
            if (!Schema::hasColumn('writing_tasks', 'title')) {
                $table->string('title')->nullable()->after('test_id');
            }
            if (!Schema::hasColumn('writing_tasks', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('writing_tasks', 'instructions')) {
                $table->text('instructions')->nullable()->after('description');
            }
            if (!Schema::hasColumn('writing_tasks', 'difficulty')) {
                $table->string('difficulty', 50)->nullable()->after('instructions');
            }
            if (!Schema::hasColumn('writing_tasks', 'word_limit')) {
                $table->integer('word_limit')->nullable()->after('min_word_count');
            }
            if (!Schema::hasColumn('writing_tasks', 'due_date')) {
                $table->timestamp('due_date')->nullable()->after('word_limit');
            }
            if (!Schema::hasColumn('writing_tasks', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('due_date');
            }
            if (!Schema::hasColumn('writing_tasks', 'questions')) {
                $table->json('questions')->nullable()->after('is_published');
            }
            
            // Add missing creator_id to track who created the task
            if (!Schema::hasColumn('writing_tasks', 'creator_id')) {
                $table->uuid('creator_id')->nullable()->after('test_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'description', 
                'instructions',
                'difficulty',
                'word_limit',
                'due_date',
                'is_published',
                'questions',
                'creator_id'
            ]);
        });
    }
};
