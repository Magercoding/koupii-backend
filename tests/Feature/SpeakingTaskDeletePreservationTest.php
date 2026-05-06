<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Assignment;
use App\Models\SpeakingTask;
use App\Models\SpeakingSubmission;
use App\Services\V1\SpeakingTask\SpeakingTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Preservation Property Tests — Speaking Task Delete
 *
 * Property 2: Preservation — Speaking Task Without Submissions Deletes Successfully
 *
 * These tests are scoped to isBugCondition(task) === false:
 *   task.submissions()->exists() === false
 *
 * They MUST PASS on the current UNFIXED code — this confirms the preservation
 * baseline that the fix must not break.
 *
 * Validates: Requirements 3.1, 3.2
 */
class SpeakingTaskDeletePreservationTest extends TestCase
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
            'title'            => 'Preservation Test Speaking Task',
            'created_by'       => $teacher->id,
            'difficulty_level' => 'beginner',
            'timer_type'       => 'none',
            'is_published'     => false,
            'is_public'        => false,
        ]);
    }

    /**
     * Helper: create an Assignment record for the given task and teacher.
     * Uses Assignment::create() directly with the required fields.
     * class_id must be a real class UUID due to the NOT NULL FK constraint.
     */
    private function createAssignment(SpeakingTask $task, User $teacher): Assignment
    {
        $class = Classes::factory()->create(['teacher_id' => $teacher->id]);

        return Assignment::create([
            'class_id'     => $class->id,
            'task_id'      => $task->id,
            'task_type'    => 'speaking_task',
            'assigned_by'  => $teacher->id,
            'title'        => 'Test Assignment for ' . $task->title,
            'status'       => 'inactive',
            'source_type'  => 'manual',
            'type'         => 'speaking',
            'is_published' => false,
        ]);
    }

    /**
     * Preservation: task with ZERO assignments and ZERO submissions.
     *
     * isBugCondition(task) === false — no submissions.
     *
     * MUST PASS on unfixed code.
     * Validates: Requirements 3.1, 3.2
     */
    public function test_delete_task_with_zero_assignments_succeeds(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $task    = $this->createTask($teacher);

        // Confirm the bug condition does NOT hold
        $this->assertFalse(
            $task->submissions()->exists(),
            'Pre-condition: task must have no submissions'
        );

        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true');
        $this->assertNull(
            SpeakingTask::find($task->id),
            'Task should be absent from the database after deletion'
        );
        $this->assertEquals(
            0,
            Assignment::where('task_id', $task->id)->count(),
            'No assignments should remain (there were none to begin with)'
        );
    }

    /**
     * Preservation: task with ONE assignment and ZERO submissions.
     *
     * isBugCondition(task) === false — no submissions.
     * The single assignment must also be deleted.
     *
     * MUST PASS on unfixed code.
     * Validates: Requirements 3.1, 3.2
     */
    public function test_delete_task_with_one_assignment_deletes_assignment(): void
    {
        $teacher    = User::factory()->create(['role' => 'teacher']);
        $task       = $this->createTask($teacher);
        $assignment = $this->createAssignment($task, $teacher);

        // Confirm the bug condition does NOT hold
        $this->assertFalse(
            $task->submissions()->exists(),
            'Pre-condition: task must have no submissions'
        );
        $this->assertEquals(
            1,
            Assignment::where('task_id', $task->id)->count(),
            'Pre-condition: task must have exactly 1 assignment'
        );

        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true');
        $this->assertNull(
            SpeakingTask::find($task->id),
            'Task should be absent from the database after deletion'
        );
        $this->assertEquals(
            0,
            Assignment::where('task_id', $task->id)->count(),
            'The assignment should be deleted along with the task'
        );
        $this->assertNull(
            Assignment::find($assignment->id),
            'The specific assignment record should be gone'
        );
    }

    /**
     * Preservation: task with MULTIPLE assignments (3–5) and ZERO submissions.
     *
     * isBugCondition(task) === false — no submissions.
     * All assignments must be deleted.
     *
     * MUST PASS on unfixed code.
     * Validates: Requirements 3.1, 3.2
     */
    public function test_delete_task_with_multiple_assignments_deletes_all(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Vary the count to exercise different scenarios (using 4 here, between 3 and 5)
        $assignmentCount = 4;
        $task            = $this->createTask($teacher);

        $assignmentIds = [];
        for ($i = 0; $i < $assignmentCount; $i++) {
            $assignmentIds[] = $this->createAssignment($task, $teacher)->id;
        }

        // Confirm the bug condition does NOT hold
        $this->assertFalse(
            $task->submissions()->exists(),
            'Pre-condition: task must have no submissions'
        );
        $this->assertEquals(
            $assignmentCount,
            Assignment::where('task_id', $task->id)->count(),
            "Pre-condition: task must have {$assignmentCount} assignments"
        );

        $result = $this->service->deleteSpeakingTask($task);

        $this->assertTrue($result, 'deleteSpeakingTask() should return true');
        $this->assertNull(
            SpeakingTask::find($task->id),
            'Task should be absent from the database after deletion'
        );
        $this->assertEquals(
            0,
            Assignment::where('task_id', $task->id)->count(),
            'All assignments should be deleted along with the task'
        );

        // Verify each individual assignment is gone
        foreach ($assignmentIds as $id) {
            $this->assertNull(
                Assignment::find($id),
                "Assignment {$id} should be deleted"
            );
        }
    }

    /**
     * Property-style preservation test: for all tasks with ZERO submissions and
     * varying assignment counts (0, 1, 2, 3, 5), deleteSpeakingTask() returns true
     * and the task is absent from the DB.
     *
     * isBugCondition(task) === false for all iterations.
     *
     * MUST PASS on unfixed code.
     * Validates: Requirements 3.1, 3.2
     */
    public function test_delete_task_returns_true_for_no_submission_tasks(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $assignmentCounts = [0, 1, 2, 3, 5];

        foreach ($assignmentCounts as $count) {
            $task = $this->createTask($teacher);

            // Create the specified number of assignments
            for ($i = 0; $i < $count; $i++) {
                $this->createAssignment($task, $teacher);
            }

            // Confirm the bug condition does NOT hold (no submissions)
            $this->assertFalse(
                $task->submissions()->exists(),
                "Pre-condition: task (assignment_count={$count}) must have no submissions"
            );
            $this->assertEquals(
                $count,
                Assignment::where('task_id', $task->id)->count(),
                "Pre-condition: task must have {$count} assignments"
            );

            $taskId = $task->id;

            $result = $this->service->deleteSpeakingTask($task);

            $this->assertTrue(
                $result,
                "deleteSpeakingTask() should return true for task with {$count} assignments and 0 submissions"
            );
            $this->assertNull(
                SpeakingTask::find($taskId),
                "Task with {$count} assignments should be absent from the database after deletion"
            );
            $this->assertEquals(
                0,
                Assignment::where('task_id', $taskId)->count(),
                "All {$count} assignments should be deleted for task with {$count} assignments"
            );
        }
    }
}
