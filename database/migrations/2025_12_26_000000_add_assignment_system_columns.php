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
        // Add missing columns to student_assignments table
        if (Schema::hasTable('student_assignments')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_assignments', 'assignment_type')) {
                    $table->string('assignment_type')->after('assignment_id')->nullable()->comment('Type: writing_task, reading_task, listening_task, speaking_task');
                }
                if (!Schema::hasColumn('student_assignments', 'attempt_count')) {
                    $table->integer('attempt_count')->after('attempt_number')->default(0);
                }
                if (!Schema::hasColumn('student_assignments', 'time_spent_seconds')) {
                    $table->integer('time_spent_seconds')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('student_assignments', 'submission_data')) {
                    $table->json('submission_data')->nullable()->after('time_spent_seconds');
                }
            });
        }

        // Add missing columns to writing_task_assignments table
        if (Schema::hasTable('writing_task_assignments')) {
            Schema::table('writing_task_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('writing_task_assignments', 'class_id')) {
                    $table->uuid('class_id')->nullable()->after('classroom_id');
                }
                if (!Schema::hasColumn('writing_task_assignments', 'due_date')) {
                    $table->datetime('due_date')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('writing_task_assignments', 'max_attempts')) {
                    $table->integer('max_attempts')->default(3)->after('due_date');
                }
                if (!Schema::hasColumn('writing_task_assignments', 'instructions')) {
                    $table->text('instructions')->nullable()->after('max_attempts');
                }
                if (!Schema::hasColumn('writing_task_assignments', 'auto_grade')) {
                    $table->boolean('auto_grade')->default(true)->after('instructions');
                }
                if (!Schema::hasColumn('writing_task_assignments', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->after('auto_grade');
                }
            });
        }

        // Add missing columns to reading_task_assignments table (if exists)
        if (Schema::hasTable('reading_task_assignments')) {
            Schema::table('reading_task_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('reading_task_assignments', 'class_id')) {
                    $table->uuid('class_id')->nullable()->after('reading_task_id');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'assigned_by')) {
                    $table->uuid('assigned_by')->nullable()->after('class_id');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'assigned_at')) {
                    $table->datetime('assigned_at')->nullable()->after('assigned_by');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'due_date')) {
                    $table->datetime('due_date')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'max_attempts')) {
                    $table->integer('max_attempts')->default(3)->after('due_date');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'instructions')) {
                    $table->text('instructions')->nullable()->after('max_attempts');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'auto_grade')) {
                    $table->boolean('auto_grade')->default(true)->after('instructions');
                }
                if (!Schema::hasColumn('reading_task_assignments', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->after('auto_grade');
                }
            });
        }

        // Add missing columns to listening_task_assignments table (if exists)
        if (Schema::hasTable('listening_task_assignments')) {
            Schema::table('listening_task_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('listening_task_assignments', 'class_id')) {
                    $table->uuid('class_id')->nullable()->after('listening_task_id');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'assigned_by')) {
                    $table->uuid('assigned_by')->nullable()->after('class_id');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'assigned_at')) {
                    $table->datetime('assigned_at')->nullable()->after('assigned_by');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'due_date')) {
                    $table->datetime('due_date')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'max_attempts')) {
                    $table->integer('max_attempts')->default(3)->after('due_date');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'instructions')) {
                    $table->text('instructions')->nullable()->after('max_attempts');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'auto_grade')) {
                    $table->boolean('auto_grade')->default(true)->after('instructions');
                }
                if (!Schema::hasColumn('listening_task_assignments', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->after('auto_grade');
                }
            });
        }

        // Add missing columns to speaking_task_assignments table (if exists)
        if (Schema::hasTable('speaking_task_assignments')) {
            Schema::table('speaking_task_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('speaking_task_assignments', 'class_id')) {
                    $table->uuid('class_id')->nullable()->after('speaking_task_id');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'assigned_by')) {
                    $table->uuid('assigned_by')->nullable()->after('class_id');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'assigned_at')) {
                    $table->datetime('assigned_at')->nullable()->after('assigned_by');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'due_date')) {
                    $table->datetime('due_date')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'max_attempts')) {
                    $table->integer('max_attempts')->default(3)->after('due_date');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'instructions')) {
                    $table->text('instructions')->nullable()->after('max_attempts');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'auto_grade')) {
                    $table->boolean('auto_grade')->default(true)->after('instructions');
                }
                if (!Schema::hasColumn('speaking_task_assignments', 'status')) {
                    $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->after('auto_grade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_assignments', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'attempt_count', 'time_spent_seconds', 'submission_data']);
        });

        Schema::table('writing_task_assignments', function (Blueprint $table) {
            $table->dropColumn(['class_id', 'due_date', 'max_attempts', 'instructions', 'auto_grade', 'status']);
        });

        if (Schema::hasTable('reading_task_assignments')) {
            Schema::table('reading_task_assignments', function (Blueprint $table) {
                $table->dropColumn(['class_id', 'assigned_by', 'assigned_at', 'due_date', 'max_attempts', 'instructions', 'auto_grade', 'status']);
            });
        }

        if (Schema::hasTable('listening_task_assignments')) {
            Schema::table('listening_task_assignments', function (Blueprint $table) {
                $table->dropColumn(['class_id', 'assigned_by', 'assigned_at', 'due_date', 'max_attempts', 'instructions', 'auto_grade', 'status']);
            });
        }

        if (Schema::hasTable('speaking_task_assignments')) {
            Schema::table('speaking_task_assignments', function (Blueprint $table) {
                $table->dropColumn(['class_id', 'assigned_by', 'assigned_at', 'due_date', 'max_attempts', 'instructions', 'auto_grade', 'status']);
            });
        }
    }
};