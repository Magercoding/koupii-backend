<?php

namespace App\Http\Resources\V1\WritingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'writing_task_id' => $this->writing_task_id,
            'student_id' => $this->student_id,
            'attempt_number' => $this->attempt_number,
            'attempt_type' => $this->attempt_type,
            'attempt_type_label' => $this->getAttemptTypeLabel(),
            'selected_questions' => $this->selected_questions,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'time_taken_seconds' => $this->time_taken_seconds,
            'duration' => $this->duration,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Computed attributes
            'is_retake' => $this->is_retake,
            'overall_score' => $this->overall_score,
            'can_submit' => $this->status === 'in_progress',
            'is_completed' => in_array($this->status, ['submitted', 'reviewed', 'completed']),
            
            // Relationships
            'writing_task' => $this->whenLoaded('writingTask', function () {
                return [
                    'id' => $this->writingTask->id,
                    'title' => $this->writingTask->title,
                    'description' => $this->writingTask->description,
                    'difficulty' => $this->writingTask->difficulty,
                    'timer_type' => $this->writingTask->timer_type,
                    'time_limit_seconds' => $this->writingTask->time_limit_seconds,
                ];
            }),
            
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'email' => $this->student->email,
                ];
            }),
            
            'submissions' => WritingSubmissionResource::collection($this->whenLoaded('submissions')),
            
            'feedback_summary' => $this->when($this->relationLoaded('feedback'), function () {
                $feedback = $this->feedback;
                
                if ($feedback->isEmpty()) {
                    return null;
                }
                
                return [
                    'total_feedback_count' => $feedback->count(),
                    'average_score' => $feedback->avg('score'),
                    'feedback_types' => $feedback->groupBy('feedback_type')->map->count(),
                    'latest_feedback' => $feedback->sortByDesc('created_at')->first() ? [
                        'score' => $feedback->sortByDesc('created_at')->first()->score,
                        'comments' => $feedback->sortByDesc('created_at')->first()->comments,
                        'created_at' => $feedback->sortByDesc('created_at')->first()->created_at,
                    ] : null,
                ];
            }),
        ];
    }
    
    /**
     * Get human-readable attempt type label
     */
    private function getAttemptTypeLabel(): string
    {
        return match($this->attempt_type) {
            'first_attempt' => 'First Attempt',
            'whole_essay' => 'Rewrite Whole Essay',
            'choose_questions' => 'Choose Any Multiple Questions',
            'specific_questions' => 'Specific Questions Only',
            default => ucfirst(str_replace('_', ' ', $this->attempt_type)),
        };
    }
    
    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'completed' => 'Completed',
            'abandoned' => 'Abandoned',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}