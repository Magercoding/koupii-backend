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
            // Add missing columns that the WritingTask model expects
            $table->string('title')->nullable()->after('test_id');
            $table->text('description')->nullable()->after('title');
            $table->text('instructions')->nullable()->after('description');
            $table->string('difficulty', 50)->nullable()->after('instructions');
            $table->integer('word_limit')->nullable()->after('min_word_count');
            $table->timestamp('due_date')->nullable()->after('word_limit');
            $table->boolean('is_published')->default(false)->after('due_date');
            $table->json('questions')->nullable()->after('is_published');
            
            // Add missing creator_id to track who created the task
            $table->uuid('creator_id')->nullable()->after('test_id');
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
