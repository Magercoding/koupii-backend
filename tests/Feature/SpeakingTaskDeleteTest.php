<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SpeakingTask;
use App\Models\SpeakingSubmission;
use App\Services\V1\SpeakingTask\SpeakingTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Bug Condition Exploration Tests — Speaking Task Delete
 *
 * Property 1: Bug Condition — Speaking Task With Submissions Throws Exception
 *
 * These tests encode the EXPECTED behavior (no exception, task deleted).
 * They FAIL on unfixed code because `deleteSpeakingTask()` throws
 * Exception('Cannot delete speaking task that has submissions').
 *
 * Validates: Requirements 1.1
 */
class SpeakingTaskDeleteTest extends TestCase
{
    use RefreshDatabase;

    private SpeakingTaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SpeakingTaskService();
    }

    /**
     * Helper: create a SpeakingTask owned by the given teacher.
     */
    private function createTask(User $teacher): SpeakingTask
    {
        return SpeakingTask::create([
            'title'            => 'Test Speaking Task',
            'created_by'       => $teacher->id,
            'difficulty_level' => 'beginner',
            'timer_type'       => 'none',
            'is_published'     => false,
            'is_public'        => false,
        ]);
    }

    /**
     * Helper: create a SpeakingSubmission for the given task and student.
     */
    private function createSubmission(SpeakingTask $task, User $student, int $attemptNumber = 1): SpeakingSubmission
    {
        return SpeakingSubmission::create([
            'speaking_task_id' => $task->id,
            'student_id'       => $student->id,
            'attempt_number'   => $attemptNumber,
            'status'           => 'submitted',
        ]);
    }

    /**
     * Bug condition: task with ONE submission.
     *
     * EXPECTED (after fix): deleteSpeakingTask() returns true, task is gone,
     * submission is gone.
     *
     * FAILS on unfixed code with:
     *   Exception('Cannot delete speaking task that has submissions')
     *
     * Counterexample: deleteSpeakingTask($taskWith1Submission) throws Exception
     * instead of returning true.
     */
    public function test_delete_speaking_task_with_one_submission(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $task = $this->createTask($teacher);
        $this->createSubmission($task, $student, 1);

        // Confirm the bug condition holds
        $this->assertTrue($task->submissions()->exists(), 'Pre-condition: task must have submissions');

        // Expected behavior: no exception, task deleted
        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true');
        $this->assertNull(SpeakingTask::find($task->id), 'Task should be deleted from the database');
        $this->assertEquals(
            0,
            SpeakingSubmission::where('speaking_task_id', $task->id)->count(),
            'All submissions should be deleted'
        );
    }

    /**
     * Bug condition: task with MULTIPLE submissions (3).
     *
     * EXPECTED (after fix): deleteSpeakingTask() returns true, task is gone,
     * all 3 submissions are gone.
     *
     * FAILS on unfixed code with:
     *   Exception('Cannot delete speaking task that has submissions')
     *
     * Counterexample: deleteSpeakingTask($taskWith3Submissions) throws Exception
     * instead of returning true.
     */
    public function test_delete_speaking_task_with_multiple_submissions(): void
    {
        $teacher  = User::factory()->create(['role' => 'teacher']);
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);

        $task = $this->createTask($teacher);

        // Three submissions — different students, each with attempt_number 1
        $this->createSubmission($task, $student1, 1);
        $this->createSubmission($task, $student2, 1);
        $this->createSubmission($task, $student3, 1);

        // Confirm the bug condition holds
        $this->assertEquals(3, $task->submissions()->count(), 'Pre-condition: task must have 3 submissions');

        // Expected behavior: no exception, task and all submissions deleted
        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true');
        $this->assertNull(SpeakingTask::find($task->id), 'Task should be deleted from the database');
        $this->assertEquals(
            0,
            SpeakingSubmission::where('speaking_task_id', $task->id)->count(),
            'All 3 submissions should be deleted'
        );
    }

    /**
     * Preservation baseline: task with NO submissions.
     *
     * This test should PASS on both unfixed and fixed code.
     * It confirms the baseline behavior that must be preserved.
     *
     * isBugCondition(task) === false here.
     */
    public function test_delete_speaking_task_with_no_submissions_still_works(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $task = $this->createTask($teacher);

        // Confirm the bug condition does NOT hold
        $this->assertFalse($task->submissions()->exists(), 'Pre-condition: task must have no submissions');

        // Expected behavior: task deleted successfully
        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true for a task with no submissions');
        $this->assertNull(SpeakingTask::find($task->id), 'Task should be deleted from the database');
        $this->assertEquals(
            0,
            SpeakingSubmission::where('speaking_task_id', $task->id)->count(),
            'Submission count should be 0 (there were none to begin with)'
        );
    }
}
