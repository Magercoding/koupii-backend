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
            $table->json('passages')->nullable()->after('questions');
            $table->json('passage_images')->nullable()->after('passages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->dropColumn(['passages', 'passage_images']);
        });
    }
};
