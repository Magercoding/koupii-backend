<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningSubmissionResource extends JsonResource
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
            'assignmentId' => $this->assignment_id,
            'taskId' => $this->listening_task_id,
            'studentId' => $this->student_id,
            'status' => $this->status,
            'totalScore' => (float) $this->total_score,
            'percentage' => (float) $this->percentage,
            'totalCorrect' => (int) $this->total_correct,
            'totalIncorrect' => (int) $this->total_incorrect,
            'totalUnanswered' => (int) $this->total_unanswered,
            'timeTakenSeconds' => $this->time_taken_seconds,
            'submittedAt' => $this->submitted_at,
            'startedAt' => $this->started_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'audioPlayCounts' => $this->audio_play_counts,

            // Task information
            'task' => $this->whenLoaded('task', function () {
                return [
                    'id' => $this->task->id,
                    'title' => $this->task->title,
                    'description' => $this->task->description,
                    'difficultyLevel' => $this->task->difficulty_level,
                    'timerType' => $this->task->timer_type,
                    'timeLimitSeconds' => $this->task->time_limit_seconds,
                    'audioUrl' => $this->task->audio_url,
                    'questions' => $this->task->questions->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'type' => $q->question_type,
                            'text' => $q->question_text,
                            'options' => $q->options,
                            'correctAnswers' => $q->correct_answers,
                            'explanation' => $q->explanation,
                            'order' => $q->order_index,
                        ];
                    }),
                ];
            }),
            
            'assignment' => $this->whenLoaded('assignment', function () {
                return [
                    'id' => $this->assignment->id,
                    'dueDate' => $this->assignment->due_date,
                    'isOverdue' => $this->assignment->is_overdue,
                    'classId' => $this->assignment->class_id,
                ];
            }),

            // Review information
            'review' => $this->whenLoaded('review', function () use ($isTeacher, $user) {
                return [
                    'id' => $this->review->id,
                    'score' => $this->review->score,
                    'comments' => $this->review->comments,
                    'feedbackJson' => $this->review->feedback_json,
                    'reviewedAt' => $this->review->reviewed_at,
                ];
            }),

            // Answer details
            'answers' => $this->whenLoaded('answers', function () {
                return $this->answers->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'questionId' => $answer->question_id,
                        'answer' => $answer->answer,
                        'isCorrect' => $answer->is_correct,
                        'pointsEarned' => (float) $answer->points_earned,
                        'timeSpentSeconds' => $answer->time_spent_seconds,
                        'audioPlayCount' => $answer->audio_play_count,
                    ];
                });
            }),

            // Computed fields
            'hasReview' => $this->review !== null,
            'isSubmitted' => $this->status !== 'to_do',
            'canRetake' => $this->when($isStudent, function () {
                return $this->task && $this->task->allowsRetakes() && 
                       ($this->attempt_number < $this->task->max_retake_attempts);
            }),
        ];
    }
}