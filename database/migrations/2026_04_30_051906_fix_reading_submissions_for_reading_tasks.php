<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('reading_submissions')) {
            Schema::table('reading_submissions', function (Blueprint $table) {
                // Ensure test_id has an index before we try to drop the composite one 
                // (MySQL requires an index for foreign keys)
                try {
                    $table->index('test_id', 'rs_test_id_manual_index');
                } catch (\Exception $e) {}

                if (!Schema::hasColumn('reading_submissions', 'assignment_id')) {
                    $table->uuid('assignment_id')->nullable()->after('reading_task_id');
                    $table->foreign('assignment_id')->references('id')->on('assignments')->nullOnDelete();
                }

                // Drop existing unique constraint on test_id
                try {
                    $table->dropUnique('reading_submissions_test_id_student_id_attempt_number_unique');
                } catch (\Exception $e) {
                    // It might have a different name or already be dropped
                }
            });

            // Add proper indexing and constraints for both Test and Task paths
            Schema::table('reading_submissions', function (Blueprint $table) {
                // Re-apply unique constraint for tests
                try {
                    $table->unique(['test_id', 'student_id', 'attempt_number'], 'rs_test_student_attempt_unique');
                } catch (\Exception $e) {}
                
                // Apply unique constraint for tasks
                try {
                    $table->unique(['reading_task_id', 'student_id', 'attempt_number'], 'rs_task_student_attempt_unique');
                } catch (\Exception $e) {}
            });
        }

        // Update reading_question_answers for reading_tasks
        if (Schema::hasTable('reading_question_answers')) {
            Schema::table('reading_question_answers', function (Blueprint $table) {
                $table->uuid('question_id')->nullable()->change();
                if (!Schema::hasColumn('reading_question_answers', 'reading_task_question_id')) {
                    $table->string('reading_task_question_id')->nullable()->after('question_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reading_question_answers')) {
            Schema::table('reading_question_answers', function (Blueprint $table) {
                $table->uuid('question_id')->nullable(false)->change();
                if (Schema::hasColumn('reading_question_answers', 'reading_task_question_id')) {
                    $table->dropColumn('reading_task_question_id');
                }
            });
        }

        if (Schema::hasTable('reading_submissions')) {
            Schema::table('reading_submissions', function (Blueprint $table) {
                try {
                    $table->dropUnique('rs_task_student_attempt_unique');
                    $table->dropUnique('rs_test_student_attempt_unique');
                    $table->unique(['test_id', 'student_id', 'attempt_number'], 'reading_submissions_test_id_student_id_attempt_number_unique');
                } catch (\Exception $e) {}
                
                if (Schema::hasColumn('reading_submissions', 'assignment_id')) {
                    $table->dropForeign(['assignment_id']);
                    $table->dropColumn('assignment_id');
                }

                try {
                    $table->dropIndex('rs_test_id_manual_index');
                } catch (\Exception $e) {}
            });
        }
    }
};
