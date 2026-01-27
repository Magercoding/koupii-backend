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
        // Enhance assignments table for automatic assignment creation
        Schema::table('assignments', function (Blueprint $table) {
            // Add source tracking for automatic assignment creation
            if (!Schema::hasColumn('assignments', 'source_type')) {
                $table->string('source_type')->default('manual')->after('is_published')
                    ->comment('Source of assignment creation: manual, auto_test');
            }
            
            if (!Schema::hasColumn('assignments', 'source_id')) {
                $table->uuid('source_id')->nullable()->after('source_type')
                    ->comment('ID of the source entity (test_id for auto_test)');
            }
            
            if (!Schema::hasColumn('assignments', 'assignment_settings')) {
                $table->json('assignment_settings')->nullable()->after('source_id')
                    ->comment('Flexible settings storage for assignment configuration');
            }
            
            if (!Schema::hasColumn('assignments', 'auto_created_at')) {
                $table->timestamp('auto_created_at')->nullable()->after('assignment_settings')
                    ->comment('Timestamp when assignment was automatically created');
            }
            
            if (!Schema::hasColumn('assignments', 'type')) {
                $table->string('type')->nullable()->after('auto_created_at')
                    ->comment('Assignment type: reading_task, writing_task, listening_task, speaking_task');
            }
        });

        // Enhance student_assignments table for better tracking
        Schema::table('student_assignments', function (Blueprint $table) {
            // Add test_id column if it doesn't exist
            if (!Schema::hasColumn('student_assignments', 'test_id')) {
                $table->uuid('test_id')->nullable()->after('student_id')
                    ->comment('Reference to the test this assignment is based on');
            }
            
            // Add assigned_at column if it doesn't exist
            if (!Schema::hasColumn('student_assignments', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('completed_at')
                    ->comment('When the assignment was assigned to the student');
            }
            
            // Add time_spent_minutes column if it doesn't exist
            if (!Schema::hasColumn('student_assignments', 'time_spent_minutes')) {
                $table->integer('time_spent_minutes')->default(0)->after('assigned_at')
                    ->comment('Time spent on assignment in minutes');
            }
            
            // Add progress_data column if it doesn't exist
            if (!Schema::hasColumn('student_assignments', 'progress_data')) {
                $table->json('progress_data')->nullable()->after('time_spent_minutes')
                    ->comment('Detailed progress tracking data');
            }
            
            // Add last_activity_at column if it doesn't exist
            if (!Schema::hasColumn('student_assignments', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('progress_data')
                    ->comment('Last time student was active on this assignment');
            }
            
            // Expand status enum to include more states
            if (Schema::hasColumn('student_assignments', 'status')) {
                $table->string('status')->change();
            }
        });

        // Create assignment_audit_trail table for status change tracking
        if (!Schema::hasTable('assignment_audit_trail')) {
            Schema::create('assignment_audit_trail', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('student_assignment_id');
                $table->string('old_status')->nullable();
                $table->string('new_status');
                $table->json('metadata')->nullable()->comment('Additional data about the status change');
                $table->uuid('changed_by')->nullable()->comment('User who triggered the change');
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->foreign('student_assignment_id')->references('id')->on('student_assignments')->cascadeOnDelete();
                $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
                
                // Indexes for performance
                $table->index(['student_assignment_id', 'changed_at'], 'audit_trail_assignment_time_idx');
                $table->index(['new_status', 'changed_at'], 'audit_trail_status_time_idx');
            });
        }

        // Add indexes for better performance (with error handling)
        $this->addIndexSafely('assignments', ['source_type', 'source_id'], 'assignments_source_idx');
        $this->addIndexSafely('assignments', ['class_id', 'type'], 'assignments_class_type_idx');
        $this->addIndexSafely('assignments', ['source_id'], 'assignments_source_id_idx');
        
        $this->addIndexSafely('student_assignments', ['assignment_id', 'status'], 'student_assignments_status_idx');
        $this->addIndexSafely('student_assignments', ['student_id', 'status'], 'student_assignments_student_status_idx');
        $this->addIndexSafely('student_assignments', ['test_id', 'student_id'], 'student_assignments_test_student_idx');

        // Add foreign key for test_id if it doesn't exist
        $this->addForeignKeySafely('student_assignments', 'test_id', 'tests', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the audit trail table
        Schema::dropIfExists('assignment_audit_trail');
        
        // Remove enhanced columns from assignments
        Schema::table('assignments', function (Blueprint $table) {
            $this->dropIndexSafely($table, ['source_type', 'source_id']);
            $this->dropIndexSafely($table, ['class_id', 'type']);
            $this->dropIndexSafely($table, 'assignments_source_id_idx');
            
            if (Schema::hasColumn('assignments', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('assignments', 'source_id')) {
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('assignments', 'assignment_settings')) {
                $table->dropColumn('assignment_settings');
            }
            if (Schema::hasColumn('assignments', 'auto_created_at')) {
                $table->dropColumn('auto_created_at');
            }
            if (Schema::hasColumn('assignments', 'type')) {
                $table->dropColumn('type');
            }
        });

        // Remove indexes from student_assignments
        Schema::table('student_assignments', function (Blueprint $table) {
            $this->dropIndexSafely($table, ['assignment_id', 'status']);
            $this->dropIndexSafely($table, ['student_id', 'status']);
            $this->dropIndexSafely($table, ['test_id', 'student_id']);
            
            try {
                $table->dropForeign(['test_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, ignore
            }
        });
    }

    /**
     * Helper method to add index safely
     */
    private function addIndexSafely(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Helper method to add foreign key safely
     */
    private function addForeignKeySafely(string $table, string $column, string $referencedTable, string $referencedColumn): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn) {
                $table->foreign($column)->references($referencedColumn)->on($referencedTable)->nullOnDelete();
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, ignore
        }
    }

    /**
     * Helper method to drop index safely
     */
    private function dropIndexSafely(Blueprint $table, $index): void
    {
        try {
            if (is_array($index)) {
                $table->dropIndex($index);
            } else {
                $table->dropIndex($index);
            }
        } catch (\Exception $e) {
            // Index might not exist, ignore
        }
    }
};