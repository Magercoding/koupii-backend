<?php

namespace App\Http\Resources\V1\ReadingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingTaskResource extends JsonResource
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
            'created_by' => $this->created_by,
            'creator_name' => optional($this->creator)->name,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'task_type' => $this->task_type,
            'difficulty' => $this->difficulty,
            'timer_type' => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,
            'allow_retake' => $this->allow_retake,
            'max_retake_attempts' => $this->max_retake_attempts,
            'retake_options' => $this->retake_options,
            'allow_submission_files' => $this->allow_submission_files,
            'is_published' => $this->is_published,
            'passages' => collect($this->passages)->map(function ($passage) use ($isStudent, $user) {
                // If it's a student, check if they have a completed submission to allow seeing answers
                $canSeeAnswers = !$isStudent;
                if ($isStudent && $this->relationLoaded('submissions')) {
                    $canSeeAnswers = $this->submissions
                        ->where('student_id', $user->id)
                        ->whereIn('status', ['submitted', 'completed', 'reviewed'])
                        ->isNotEmpty();
                }

                if (isset($passage['question_groups'])) {
                    $groups = collect($passage['question_groups'])->map(function ($group) use ($canSeeAnswers) {
                        if (isset($group['questions'])) {
                            $group['questions'] = collect($group['questions'])->map(function ($q) use ($canSeeAnswers) {
                                // If not authorized, remove correct answers
                                if (!$canSeeAnswers) {
                                    unset($q['correct_answers']);
                                    unset($q['correct_answer']);
                                }
                                return $q;
                            })->toArray();
                        }
                        return $group;
                    })->toArray();
                    
                    $passage['questionGroups'] = $groups;
                    unset($passage['question_groups']);
                }
                return $passage;
            })->toArray(),
            'vocabularies' => $this->vocabularies,
            'passage_images' => $this->passage_images,
            'suggest_time_minutes' => $this->suggest_time_minutes,
            'difficulty_level' => $this->difficulty_level,
            'question_types' => $this->question_types,
            'total_questions' => $this->total_questions,
            'estimated_time' => $this->estimated_time,
            'formatted_timer' => $this->formatted_timer,
            'has_timer' => $this->hasTimer(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Student-specific data
            'my_submissions' => $this->when($isStudent, function () use ($user) {
                return $this->whenLoaded('submissions', function () use ($user) {
                    return $this->submissions->where('student_id', $user->id)->map(function ($submission) {
                        return [
                            'id' => $submission->id,
                            'status' => $submission->status,
                            'score' => $submission->score,
                            'submitted_at' => $submission->submitted_at,
                            'has_review' => isset($submission->review_id) && $submission->review_id !== null,
                        ];
                    });
                });
            }),

            'current_submission_status' => $this->when($isStudent, function () use ($user) {
                return $this->whenLoaded('submissions', function () use ($user) {
                    $latestSubmission = $this->submissions->where('student_id', $user->id)->first();
                    return $latestSubmission ? $latestSubmission->status : 'to_do';
                });
            }),

            // Teacher/Admin data
            'assignments' => $this->when($isTeacher || ($user && $user->role === 'admin'), function () {
                return $this->whenLoaded('assignments', function () {
                    return $this->assignments->map(function ($assignment) {
                        return [
                            'id'             => $assignment->id,
                            'classroom_id'   => $assignment->class_id,
                            'classroom_name' => optional($assignment->class)->name,
                            'due_date'       => $assignment->due_date,
                            'assigned_at'    => $assignment->created_at,
                        ];
                    });
                });
            }),

            'submissions_summary' => $this->when($isTeacher || ($user && $user->role === 'admin'), function () {
                return $this->whenLoaded('submissions', function () {
                    $submissions = $this->submissions;
                    return [
                        'total' => $submissions->count(),
                        'submitted' => $submissions->where('status', 'submitted')->count(),
                        'reviewed' => $submissions->where('status', 'reviewed')->count(),
                        'done' => $submissions->where('status', 'done')->count(),
                        'average_score' => $submissions->where('score', '>', 0)->avg('score'),
                        'completion_rate' => $submissions->count() > 0 
                            ? ($submissions->where('status', 'done')->count() / $submissions->count()) * 100 
                            : 0,
                    ];
                });
            }),

            // Statistics for admin/teacher view
            'statistics' => $this->when($user && in_array($user->role, ['admin', 'teacher']), function () {
                return [
                    'difficulty_rating' => $this->difficulty,
                    'estimated_completion_time' => $this->estimated_time . ' minutes',
                    'retake_allowed' => $this->allowsRetakes(),
                    'has_file_uploads' => $this->allow_submission_files,
                    'publication_status' => $this->is_published ? 'Published' : 'Draft',
                ];
            }),

            // Permissions
            'permissions' => $this->when($user, function () use ($user, $isTeacher, $isStudent) {
                return [
                    'can_edit' => $user->role === 'admin' || ($isTeacher && $this->created_by === $user->id),
                    'can_delete' => $user->role === 'admin' || ($isTeacher && $this->created_by === $user->id),
                    'can_assign' => $user->role === 'admin' || $isTeacher,
                    'can_view_submissions' => $user->role === 'admin' || ($isTeacher && $this->created_by === $user->id),
                    'can_submit' => $isStudent && $this->is_published,
                ];
            }),
        ];
    }
}