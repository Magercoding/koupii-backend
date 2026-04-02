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
        Schema::table('reading_submissions', function (Blueprint $table) {
            $table->uuid('reading_task_id')->nullable()->after('test_id');
            $table->uuid('test_id')->nullable()->change();
            
            $table->foreign('reading_task_id')->references('id')->on('reading_tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_submissions', function (Blueprint $table) {
            $table->dropForeign(['reading_task_id']);
            $table->dropColumn('reading_task_id');
            $table->uuid('test_id')->nullable(false)->change();
        });
    }
};
