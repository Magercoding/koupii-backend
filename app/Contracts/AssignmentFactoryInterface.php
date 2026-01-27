<?php

namespace App\Contracts;

use App\Models\Assignment;
use App\Models\Test;

/**
 * Interface for standardized assignment creation across different test types
 * Provides consistent assignment creation and student assignment generation
 */
interface AssignmentFactoryInterface
{
    /**
     * Create an assignment from a test with optional configuration
     *
     * @param Test $test The test to create an assignment from
     * @param array $options Optional configuration (due_date, instructions, etc.)
     * @return Assignment The created assignment
     * @throws \Exception If assignment creation fails
     */
    public function createFromTest(Test $test, array $options = []): Assignment;

    /**
     * Create student assignments for all enrolled students in the assignment's class
     *
     * @param Assignment $assignment The assignment to create student assignments for
     * @return int The number of student assignments created
     * @throws \Exception If student assignment creation fails
     */
    public function createStudentAssignments(Assignment $assignment): int;
}