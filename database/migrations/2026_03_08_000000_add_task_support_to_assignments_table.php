<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add task_id and task_type columns to assignments table
     * to support both test-based and task-based assignments.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'task_id')) {
                $table->uuid('task_id')->nullable()->after('test_id')
                    ->comment('ID of the task (writing, reading, listening, speaking)');
            }

            if (!Schema::hasColumn('assignments', 'task_type')) {
                $table->string('task_type')->nullable()->after('task_id')
                    ->comment('Type of task: writing_task, reading_task, listening_task, speaking_task');
            }

            if (!Schema::hasColumn('assignments', 'assigned_by')) {
                $table->uuid('assigned_by')->nullable()->after('task_type')
                    ->comment('Teacher who created the assignment');
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('assignments', 'max_attempts')) {
                $table->integer('max_attempts')->default(3)->after('is_published');
            }

            if (!Schema::hasColumn('assignments', 'instructions')) {
                $table->text('instructions')->nullable()->after('max_attempts');
            }

            if (!Schema::hasColumn('assignments', 'status')) {
                $table->string('status')->default('active')->after('instructions')
                    ->comment('Assignment status: active, inactive, archived');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $columns = ['task_id', 'task_type', 'assigned_by', 'max_attempts', 'instructions', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('assignments', $column)) {
                    if ($column === 'assigned_by') {
                        $table->dropForeign(['assigned_by']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
