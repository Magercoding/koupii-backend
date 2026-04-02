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
        if (Schema::hasTable('speaking_submissions')) {
            Schema::table('speaking_submissions', function (Blueprint $col) {
                if (!Schema::hasColumn('speaking_submissions', 'assignment_id')) {
                    $col->uuid('assignment_id')->nullable()->after('student_id');
                    $col->foreign('assignment_id')->references('id')->on('assignments')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('speaking_submissions')) {
            Schema::table('speaking_submissions', function (Blueprint $col) {
                if (Schema::hasColumn('speaking_submissions', 'assignment_id')) {
                    $col->dropForeign(['assignment_id']);
                    $col->dropColumn('assignment_id');
                }
            });
        }
    }
};
