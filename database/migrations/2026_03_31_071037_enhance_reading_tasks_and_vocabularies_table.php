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
        Schema::table('reading_tasks', function (Blueprint $table) {
            $table->json('vocabularies')->nullable()->after('passages');
        });

        Schema::table('vocabulary_categories', function (Blueprint $table) {
            $table->uuid('teacher_id')->nullable()->after('id');
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vocabulary_categories', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });

        Schema::table('reading_tasks', function (Blueprint $table) {
            $table->dropColumn('vocabularies');
        });
    }
};
