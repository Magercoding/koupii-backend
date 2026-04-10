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
        Schema::table('writing_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('writing_submissions', 'assignment_id')) {
                $table->uuid('assignment_id')->nullable()->after('student_id')->index();
            }
        });

        Schema::table('writing_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('writing_attempts', 'assignment_id')) {
                $table->uuid('assignment_id')->nullable()->after('student_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('writing_submissions', function (Blueprint $table) {
            $table->dropColumn('assignment_id');
        });

        Schema::table('writing_attempts', function (Blueprint $table) {
            $table->dropColumn('assignment_id');
        });
    }
};
