<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('writing_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('test_id');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('writing_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
        });
    }
};
