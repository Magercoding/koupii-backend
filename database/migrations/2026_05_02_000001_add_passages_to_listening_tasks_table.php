<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add passage_index to listening_questions so each question knows its passage
        Schema::table('listening_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_questions', 'passage_index')) {
                $table->unsignedInteger('passage_index')->default(0)->after('listening_task_id');
            }
        });

        // Add passages_data JSON to listening_tasks to store per-passage audio + metadata
        Schema::table('listening_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_tasks', 'passages_data')) {
                $table->json('passages_data')->nullable()->after('audio_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listening_questions', function (Blueprint $table) {
            $table->dropColumn('passage_index');
        });

        Schema::table('listening_tasks', function (Blueprint $table) {
            $table->dropColumn('passages_data');
        });
    }
};
