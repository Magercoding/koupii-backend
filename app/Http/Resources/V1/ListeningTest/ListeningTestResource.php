<?php

namespace App\Http\Resources\V1\ListeningTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = $request->user();
        $isStudent = $user && $user->role === 'student';

        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'creator_name' => optional($this->creator)->name,
            'type' => $this->type, // 'listening'
            'test_type' => $this->test_type,
            'difficulty' => $this->difficulty,
            'title' => $this->title,
            'description' => $this->description,
            'timer_mode' => $this->timer_mode,
            'timer_settings' => $this->timer_settings,
            'allow_repetition' => $this->allow_repetition,
            'max_repetition_count' => $this->max_repetition_count,
            'is_public' => $this->is_public,
            'is_published' => $this->is_published,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Statistics for teacher/admin
            'statistics' => $this->when(!$isStudent, function () {
                return [
                    'total_attempts' => $this->test_attempts_count ?? 0,
                    'completed_attempts' => $this->completed_attempts_count ?? 0,
                    'average_score' => $this->average_score ?? null,
                ];
            }),

            // Audio segments if loaded
            'audio_segments' => $this->when(
                $this->relationLoaded('listeningAudioSegments'),
                function () {
                    return $this->listeningAudioSegments->map(function ($segment) {
                        return [
                            'id' => $segment->id,
                            'title' => $segment->title,
                            'audio_url' => $segment->audio_url,
                            'transcript' => $segment->transcript,
                            'duration' => $segment->duration,
                            'segment_type' => $segment->segment_type,
                            'difficulty_level' => $segment->difficulty_level,
                            'questions_count' => $segment->questions_count ?? 0,
                        ];
                    });
                }
            ),

            // Questions if loaded
            'questions' => $this->when(
                $this->relationLoaded('listeningQuestions'),
                function () {
                    return $this->listeningQuestions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'audio_segment_id' => $question->audio_segment_id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'time_range' => $question->time_range,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer,
                            'explanation' => $question->explanation,
                            'points' => $question->points,
                        ];
                    });
                }
            ),

            // Include submission data for students if available
            'user_submission' => $this->when($isStudent && $this->relationLoaded('userSubmission'), function () {
                $submission = $this->userSubmission;
                return $submission ? [
                    'id' => $submission->id,
                    'score' => $submission->score,
                    'status' => $submission->status,
                    'started_at' => $submission->started_at,
                    'completed_at' => $submission->completed_at,
                    'time_spent' => $submission->time_spent,
                ] : null;
            }),

            // Performance metrics for teachers/admins
            'performance_metrics' => $this->when(!$isStudent, function () {
                return [
                    'average_completion_time' => $this->average_completion_time ?? null,
                    'difficulty_rating' => $this->difficulty_rating ?? null,
                    'most_missed_questions' => $this->most_missed_questions ?? [],
                    'completion_rate' => $this->completion_rate ?? null,
                ];
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'test_type' => 'listening',
                'version' => '1.0',
            ],
        ];
    }
}