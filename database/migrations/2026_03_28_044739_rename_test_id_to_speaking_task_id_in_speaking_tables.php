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
        if (Schema::hasColumn('speaking_task_assignments', 'test_id')) {
            Schema::table('speaking_task_assignments', function (Blueprint $table) {
                $table->dropForeign(['test_id']);
                $table->renameColumn('test_id', 'speaking_task_id');
                $table->foreign('speaking_task_id')->references('id')->on('speaking_tasks')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('speaking_submissions', 'test_id')) {
            Schema::table('speaking_submissions', function (Blueprint $table) {
                $table->dropForeign(['test_id']);
                // Drop unique constraint since it relies on test_id
                $table->dropUnique(['test_id', 'student_id', 'attempt_number']);
                $table->renameColumn('test_id', 'speaking_task_id');
                $table->foreign('speaking_task_id')->references('id')->on('speaking_tasks')->onDelete('cascade');
                $table->unique(['speaking_task_id', 'student_id', 'attempt_number'], 'spk_subm_task_student_attempt_unq');
            });
        } else {
             // In case rename happened but unique constraint failed (MySQL limitation)
             $indexes = collect(Schema::getIndexes('speaking_submissions'))->pluck('name');
             if (!$indexes->contains('spk_subm_task_student_attempt_unq')) {
                 Schema::table('speaking_submissions', function (Blueprint $table) {
                     $table->unique(['speaking_task_id', 'student_id', 'attempt_number'], 'spk_subm_task_student_attempt_unq');
                 });
             }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('speaking_submissions', function (Blueprint $table) {
            $table->dropForeign(['speaking_task_id']);
            $table->dropUnique('spk_subm_task_student_attempt_unq');
            $table->renameColumn('speaking_task_id', 'test_id');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->unique(['test_id', 'student_id', 'attempt_number']);
        });

        Schema::table('speaking_task_assignments', function (Blueprint $table) {
            $table->dropForeign(['speaking_task_id']);
            $table->renameColumn('speaking_task_id', 'test_id');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
        });
    }
};
