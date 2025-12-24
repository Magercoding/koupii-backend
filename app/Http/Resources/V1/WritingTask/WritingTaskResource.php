<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isStudent = $user && $user->role === 'student';
        $isTeacher = $user && $user->role === 'teacher';

        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'creator_name' => optional($this->creator)->name,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'difficulty' => $this->difficulty,
            'difficulty_label' => $this->getDifficultyLabel(),
            'word_limit' => $this->word_limit,
            'timer_type' => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,
            'allow_submission_files' => $this->allow_submission_files,
            'is_published' => $this->is_published,
            'is_overdue' => $this->isOverdue(),
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Questions
            'questions' => $this->questions ?? [],
            'questions_count' => is_array($this->questions) ? count($this->questions) : 0,

            // Retake settings
            'allow_retake' => $this->allow_retake,
            'max_retake_attempts' => $this->max_retake_attempts,
            'retake_options' => $this->retake_options,

            // Hide sample answer from students
            'sample_answer' => $this->when(!$isStudent, $this->sample_answer),

            // Include assignments for teachers/admins
            'assignments' => $this->when(
                ($isTeacher || $user->role === 'admin') && $this->relationLoaded('assignments'),
                $this->assignments->map(function($assignment) {
                    return [
                        'id' => $assignment->id,
                        'classroom_id' => $assignment->classroom_id,
                        'due_date' => $assignment->due_date,
                        'assigned_at' => $assignment->assigned_at
                    ];
                })
            ),

            // Include submissions summary
            'submissions_count' => $this->when(
                $this->relationLoaded('submissions'),
                $this->submissions->count()
            ),

         
            'statistics' => $this->when($isTeacher || $user->role === 'admin', function () {
                $submissions = $this->submissions;
                return [
                    'total_submissions' => $submissions->count(),
                    'submitted_count' => $submissions->where('status', 'submitted')->count(),
                    'reviewed_count' => $submissions->where('status', 'reviewed')->count(),
                    'done_count' => $submissions->where('status', 'done')->count(),
                    'average_score' => $submissions->whereNotNull('review.score')->avg('review.score'),
                    'average_word_count' => $submissions->avg('word_count'),
                ];
            }),

       
            'student_status' => $this->when($isStudent, function () use ($user) {
                $latestSubmission = $this->submissions
                    ->where('student_id', $user->id)
                    ->sortByDesc('attempt_number')
                    ->first();

                if (!$latestSubmission) {
                    return [
                        'status' => 'to_do',
                        'attempt_number' => 0,
                        'can_retake' => false,
                        'score' => null,
                    ];
                }

                return [
                    'status' => $latestSubmission->status,
                    'attempt_number' => $latestSubmission->attempt_number,
                    'can_retake' => $this->allow_retake &&
                        $latestSubmission->status === 'reviewed' &&
                        (!$this->max_retake_attempts || $latestSubmission->attempt_number < $this->max_retake_attempts),
                    'score' => optional($latestSubmission->review)->score,
                    'submitted_at' => $latestSubmission->submitted_at,
                ];
            }),
        ];
    }
}
