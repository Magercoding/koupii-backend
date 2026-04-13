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
        Schema::table('speaking_reviews', function (Blueprint $table) {
            $table->json('skill_scores')->nullable()->after('overall_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('speaking_reviews', function (Blueprint $table) {
            $table->dropColumn('skill_scores');
        });
    }
};
