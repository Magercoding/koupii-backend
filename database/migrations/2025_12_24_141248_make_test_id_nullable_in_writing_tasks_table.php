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
            // Make test_id nullable since WritingTasks can now exist independently
            $table->uuid('test_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            // Revert test_id back to not nullable
            $table->uuid('test_id')->nullable(false)->change();
        });
    }
};
