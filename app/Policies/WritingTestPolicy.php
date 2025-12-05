<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Test;



class WritingTestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }


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
        return in_array($user->role, ['admin', 'teacher']);
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

        // Teachers can only update their own tests
        return $user->role === 'teacher' && $test->creator_id === $user->id;
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

        // Teachers can only delete their own tests
        return $user->role === 'teacher' && $test->creator_id === $user->id;
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

        // Teachers can publish/unpublish their own tests
        return $user->role === 'teacher' && $test->creator_id === $user->id;
    }

    /**
     * Determine if the user can take the test.
     */
    public function take(User $user, Test $test): bool
    {
        // Only students can take tests
        if ($user->role !== 'student') {
            return false;
        }

        // Test must be published
        return $test->is_published;
    }
}
