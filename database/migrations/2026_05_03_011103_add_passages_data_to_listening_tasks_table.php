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
        Schema::table('listening_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_tasks', 'passages_data')) {
                $table->json('passages_data')->nullable()->after('audio_segments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listening_tasks', function (Blueprint $table) {
            $table->dropColumn('passages_data');
        });
    }
};
