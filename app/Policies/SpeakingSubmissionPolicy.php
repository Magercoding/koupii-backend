<?php

namespace App\Policies;

use App\Models\SpeakingSubmission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpeakingSubmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SpeakingSubmission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            // Teacher can view if they created the task
            if ($submission->speakingTask?->created_by === $user->id) {
                return true;
            }

            // Or if they are the teacher of the class assigned this task
            return $submission->assignment?->class?->teacher_id === $user->id || 
                   $submission->assignment?->assigned_by === $user->id ||
                   $submission->studentAssignment?->assignment?->assigned_by === $user->id;
        }

        // Student can view their own submission
        return $submission->student_id === $user->id;
    }

    /**
     * Determine whether the user can update the model (upload recordings / submit).
     */
    public function update(User $user, SpeakingSubmission $submission): bool
    {
        // Only the student who owns the submission can update it
        // Allow in_progress (recording/submitting) and submitted (idempotent re-submit)
        return $submission->student_id === $user->id &&
               in_array($submission->status, ['in_progress', 'to_do', 'submitted']);
    }

    /**
     * Determine whether the user can review the submission.
     */
    public function review(User $user, SpeakingSubmission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'teacher') {
            // Only teachers who created the task (or assigned it) can review
            return $submission->speakingTask?->created_by === $user->id ||
                   $submission->assignment?->class?->teacher_id === $user->id ||
                   $submission->assignment?->assigned_by === $user->id ||
                   $submission->studentAssignment?->assignment?->assigned_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SpeakingSubmission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Student can delete their own in-progress submission
        return $submission->student_id === $user->id && $submission->status === 'in_progress';
    }
}
