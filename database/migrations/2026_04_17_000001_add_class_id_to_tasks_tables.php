<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('reading_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('test_id');
                $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                $table->index(['class_id']);
            }
        });

        Schema::table('listening_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('id');
                $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                $table->index(['class_id']);
            }
        });

        Schema::table('speaking_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('speaking_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('id');
                $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                $table->index(['class_id']);
            }
        });

        Schema::table('writing_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('writing_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('test_id');
                $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
                $table->index(['class_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('writing_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('writing_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropIndex(['class_id']);
                $table->dropColumn('class_id');
            }
        });

        Schema::table('speaking_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('speaking_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropIndex(['class_id']);
                $table->dropColumn('class_id');
            }
        });

        Schema::table('listening_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('listening_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropIndex(['class_id']);
                $table->dropColumn('class_id');
            }
        });

        Schema::table('reading_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('reading_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropIndex(['class_id']);
                $table->dropColumn('class_id');
            }
        });
    }
};

