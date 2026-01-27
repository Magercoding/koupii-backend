<?php

namespace App\Listeners;

use App\Events\TestAssignedToClass;
use App\Contracts\AssignmentFactoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener that creates assignments when a test is assigned to a class
 * Uses AssignmentFactory for standardized assignment creation
 */
class CreateAssignmentsForTest implements ShouldQueue
{
    use InteractsWithQueue;

    private AssignmentFactoryInterface $assignmentFactory;

    public function __construct(AssignmentFactoryInterface $assignmentFactory)
    {
        $this->assignmentFactory = $assignmentFactory;
    }

    /**
     * Handle the event.
     *
     * @param TestAssignedToClass $event
     * @return void
     */
    public function handle(TestAssignedToClass $event): void
    {
        try {
            DB::beginTransaction();

            // Create the main assignment record using the factory
            $assignment = $this->assignmentFactory->createFromTest($event->test, $event->options);

            // Create student assignments for all enrolled students using the factory
            $studentCount = $this->assignmentFactory->createStudentAssignments($assignment);

            DB::commit();

            Log::info('Automatic assignment created successfully', [
                'test_id' => $event->test->id,
                'class_id' => $event->class->id,
                'assignment_id' => $assignment->id,
                'student_count' => $studentCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create automatic assignment', [
                'test_id' => $event->test->id,
                'class_id' => $event->class->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to allow for proper error handling
            throw $e;
        }
    }
}