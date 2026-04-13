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
        Schema::table('speaking_recordings', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('speaking_recordings', function (Blueprint $table) {
            // Restore the foreign key constraint
            $table->foreign('question_id')
                ->references('id')
                ->on('speaking_questions')
                ->onDelete('cascade');
        });
    }
};
