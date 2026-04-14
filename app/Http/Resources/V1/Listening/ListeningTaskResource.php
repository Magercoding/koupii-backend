<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\Listening\ListeningSubmissionResource;

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
        $isAdmin   = $user && $user->role === 'admin';

        return [
            'id'                 => $this->id,
            'creator_id'         => $this->created_by,
            'creator_name'       => optional($this->creator)->name,
            'title'              => $this->title,
            'description'        => $this->description,
            'instructions'       => $this->instructions,
            'timer_type'         => $this->timer_type,
            'time_limit_seconds' => $this->time_limit_seconds,
            'difficulty_level'   => $this->difficulty_level ?? $this->difficulty,
            'difficulty'         => $this->difficulty_level ?? $this->difficulty,
            'is_published'       => $this->is_published,
            // Support both column names
            'audio_url'          => $this->audio_url ?? $this->audio_file_url,
            'audio_file_url'     => $this->audio_file_url ?? $this->audio_url,
            'max_plays'          => $this->max_plays ?? null,
            'allow_replay'       => $this->allow_replay ?? true,
            'transcript'         => $this->transcript,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,

            // CRUD permission flags for frontend
            'can_edit'   => $user && ($user->role === 'admin' || ($user->role === 'teacher' && $this->created_by === $user->id)),
            'can_delete' => $user && ($user->role === 'admin' || ($user->role === 'teacher' && $this->created_by === $user->id)),

            // Questions — exposed to ALL authenticated users
            'questions' => $this->whenLoaded('questions', function () use ($isStudent, $user) {
                // If it's a student, check if they have a completed submission to allow seeing answers
                $canSeeAnswers = !$isStudent;
                if ($isStudent && $this->relationLoaded('submissions')) {
                    $canSeeAnswers = $this->submissions
                        ->where('student_id', $user->id)
                        ->whereIn('status', ['submitted', 'reviewed', 'done'])
                        ->isNotEmpty();
                }

                return $this->questions->sortBy(fn($q) => $q->order_index ?? $q->order ?? 0)
                    ->map(function ($q) use ($canSeeAnswers) {
                        $data = [
                            'id'            => $q->id,
                            'question_type' => $q->question_type,
                            'question_text' => $q->question_text,
                            'order'         => $q->order_index ?? $q->order ?? 0,
                            'points'        => $q->points,
                            'options'       => collect($q->options ?? [])->map(function ($opt, $idx) {
                                if (is_string($opt)) {
                                    return ['id' => (string) $idx, 'text' => $opt];
                                }
                                return $opt;
                            })->values()->all(),
                        ];

                        // Expose correct answers if authorized
                        if ($canSeeAnswers) {
                            $data['correct_answers'] = $q->correct_answers;
                            $data['correct_answer']  = $q->correct_answer ?? (is_array($q->correct_answers) ? ($q->correct_answers[0] ?? null) : $q->correct_answers);
                            $data['explanation']     = $q->explanation;
                        }

                        return $data;
                    })->values()->all();
            }),

            // Counts (always useful)
            'total_questions' => $this->whenLoaded('questions', fn() => $this->questions->count()),
            'total_points'    => $this->whenLoaded('questions', fn() => $this->questions->sum('points')),

            // Student-specific - Use the dedicated resource for consistency
            'my_submissions' => $this->when($isStudent && $this->relationLoaded('submissions'), function () {
                return ListeningSubmissionResource::collection($this->submissions);
            }),

            // Alias for frontend compatibility
            'submissions' => $this->when($isStudent && $this->relationLoaded('submissions'), function () {
                return ListeningSubmissionResource::collection($this->submissions);
            }),

            'latest_submission' => $this->when($isStudent && $this->relationLoaded('submissions'), function () use ($user) {
                $latest = $this->submissions->where('student_id', $user->id)->first();
                return $latest ? new ListeningSubmissionResource($latest) : null;
            }),

            'latestSubmission' => $this->when($isStudent && $this->relationLoaded('submissions'), function () use ($user) {
                $latest = $this->submissions->where('student_id', $user->id)->first();
                return $latest ? new ListeningSubmissionResource($latest) : null;
            }),

            // Teacher/Admin only
            'submissions_summary' => $this->when($isTeacher || $isAdmin, function () {
                return $this->whenLoaded('submissions', function () {
                    $subs = $this->submissions;
                    return [
                        'total'         => $subs->count(),
                        'submitted'     => $subs->where('status', 'submitted')->count(),
                        'reviewed'      => $subs->where('status', 'reviewed')->count(),
                        'average_score' => $subs->count() > 0 ? round($subs->where('percentage', '>', 0)->avg('percentage') ?? 0, 2) : 0,
                    ];
                });
            }),
        ];
    }
}