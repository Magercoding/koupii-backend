<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speaking_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('speaking_tasks', 'timer_type')) {
                $table->string('timer_type')->default('none')->after('time_limit_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('speaking_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('speaking_tasks', 'timer_type')) {
                $table->dropColumn('timer_type');
            }
        });
    }
};
