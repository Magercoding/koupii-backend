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
        Schema::table('listening_question_answers', function (Blueprint $table) {
            // First, ensure we don't have duplicates that would break the unique index
            // (Optional but safer if there's existing data)
            
            $table->unique(['submission_id', 'question_id'], 'sub_quest_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listening_question_answers', function (Blueprint $table) {
            $table->dropUnique('sub_quest_unique');
        });
    }
};
