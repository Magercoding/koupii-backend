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
            $table->boolean('is_public')->default(false)->after('max_retake_attempts');
        });
        Schema::table('listening_tasks', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('max_retake_attempts');
        });
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('max_retake_attempts');
        });
        Schema::table('speaking_tasks', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('time_limit_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_tasks', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
        Schema::table('listening_tasks', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
        Schema::table('speaking_tasks', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
