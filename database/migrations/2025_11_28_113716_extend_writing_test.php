<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
     /**
     * Run the migrations.
     */
        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->boolean('allow_retake')->default(false);
            $table->integer('max_retake_attempts')->nullable();
            $table->json('retake_options')->nullable(); // e.g. ["rewrite_all","group_similar","choose_any"]
            $table->enum('timer_type', ['none', 'countdown', 'countup'])->default('none');
            $table->integer('time_limit_seconds')->nullable();
            $table->boolean('allow_submission_files')->default(false);
        });

        // assignments: which class(es) the task was sent to
        Schema::create('writing_task_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('writing_task_id');
            $table->uuid('classroom_id')->nullable(); // no FK to avoid coupling if classroom table differs
            $table->uuid('assigned_by')->nullable(); // teacher id (uuid)
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('writing_task_id')->references('id')->on('writing_tasks')->onDelete('cascade');
        });

        // student submissions
        Schema::create('writing_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('writing_task_id');
            $table->uuid('student_id');
            $table->text('content')->nullable();
            $table->json('files')->nullable(); // optional uploaded files
            $table->integer('word_count')->nullable();
            $table->enum('status', ['to_do', 'submitted', 'reviewed', 'done'])->default('to_do');
            $table->integer('attempt_number')->default(1);
            $table->integer('time_taken_seconds')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('writing_task_id')->references('id')->on('writing_tasks')->onDelete('cascade');
        });

        
        Schema::create('writing_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('teacher_id')->nullable();
            $table->integer('score')->nullable();
            $table->text('comments')->nullable();
            $table->json('feedback_json')->nullable(); // structured feedback / highlights
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('writing_submissions')->onDelete('cascade');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_reviews');
        Schema::dropIfExists('writing_submissions');
        Schema::dropIfExists('writing_task_assignments');

        Schema::table('writing_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'allow_retake',
                'max_retake_attempts',
                'retake_options',
                'timer_type',
                'time_limit_seconds',
                'allow_submission_files',
            ]);
        });
    }
};