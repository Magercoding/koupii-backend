<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningTaskResource extends JsonResource
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
            'timer_type' => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,
            'difficulty_level' => $this->difficulty_level,
            'retakes_allowed' => $this->retakes_allowed,
            'max_retakes' => $this->max_retakes,
            'auto_mark' => $this->auto_mark,
            'feedback_enabled' => $this->feedback_enabled,
            'is_published' => $this->is_published,
            'audio_file_url' => $this->audio_file_url,
            'transcript' => $this->transcript,
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
                            'has_review' => $submission->review !== null,
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
                            'id' => $assignment->id,
                            'classroom_id' => $assignment->classroom_id,
                            'classroom_name' => optional($assignment->classroom)->name,
                            'assigned_at' => $assignment->created_at,
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
                    ];
                });
            }),

            // Questions (for task creation/editing)
            'questions' => $this->when($user && in_array($user->role, ['admin', 'teacher']), function () {
                return $this->whenLoaded('questions', function () {
                    return $this->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_type' => $question->question_type,
                            'question_text' => $question->question_text,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer,
                            'points' => $question->points,
                            'order' => $question->order,
                        ];
                    });
                });
            }),

            // Computed fields
            'total_questions' => $this->whenLoaded('questions', fn() => $this->questions->count()),
            'total_points' => $this->whenLoaded('questions', fn() => $this->questions->sum('points')),
            'assigned_classrooms_count' => $this->whenLoaded('assignments', fn() => $this->assignments->count()),
        ];
    }
}