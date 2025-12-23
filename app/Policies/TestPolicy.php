<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Test;

class TestPolicy
{
    /**
     * Determine if the user can view any tests.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view tests (with role-based filtering in controller)
    }

    /**
     * Determine if the user can view the test.
     */
    public function view(User $user, Test $test): bool
    {
        // Admin can view any test
        if ($user->role === 'admin') {
            return true;
        }

        // Students can only view published tests
        if ($user->role === 'student') {
            return $test->is_published;
        }

        // Teachers can view their own tests
        return $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can create tests.
     */
    public function create(User $user): bool
    {
        // Only teachers and admins can create tests
        return in_array($user->role, ['teacher', 'admin']);
    }

    /**
     * Determine if the user can update the test.
     */
    public function update(User $user, Test $test): bool
    {
        // Admin can update any test
        if ($user->role === 'admin') {
            return true;
        }

        // Test creator can update their own test
        return $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can delete the test.
     */
    public function delete(User $user, Test $test): bool
    {
        // Admin can delete any test
        if ($user->role === 'admin') {
            return true;
        }

        // Test creator can delete their own test
        return $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can publish/unpublish the test.
     */
    public function publish(User $user, Test $test): bool
    {
        // Admin can publish/unpublish any test
        if ($user->role === 'admin') {
            return true;
        }

        // Test creator can publish/unpublish their own test
        return $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can duplicate the test.
     */
    public function duplicate(User $user, Test $test): bool
    {
        // Admin can duplicate any test
        if ($user->role === 'admin') {
            return true;
        }

        // Teachers can duplicate published tests or their own tests
        if ($user->role === 'teacher') {
            return $test->is_published || $test->creator_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can assign the test.
     */
    public function assign(User $user, Test $test): bool
    {
        // Admin can assign any published test
        if ($user->role === 'admin') {
            return $test->is_published;
        }

        // Teachers can assign published tests or their own tests
        if ($user->role === 'teacher') {
            return $test->is_published || $test->creator_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can view test statistics.
     */
    public function viewStatistics(User $user, Test $test): bool
    {
        // Admin can view any test statistics
        if ($user->role === 'admin') {
            return true;
        }

        // Test creator can view their own test statistics
        return $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can take/attempt the test.
     */
    public function attempt(User $user, Test $test): bool
    {
        // Students can only attempt published tests
        if ($user->role === 'student') {
            return $test->is_published;
        }

        // Teachers and admins can attempt any test for preview purposes
        return in_array($user->role, ['teacher', 'admin']);
    }
}