<?php

namespace App\Http\Resources\V1\WritingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WritingTestResource extends JsonResource
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
            'type' => $this->type, // 'writing'
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

            // If you want to include related writing tasks (if there's a relationship)
            'writing_tasks' => $this->when(
                $this->relationLoaded('writingTasks'),
                function () {
                    return $this->writingTasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'is_published' => $task->is_published,
                            'due_date' => $task->due_date,
                        ];
                    });
                }
            ),

            // Test metadata
            'meta' => [
                'can_edit' => !$isStudent && ($user->role === 'admin' || $this->creator_id === $user->id),
                'can_delete' => !$isStudent && ($user->role === 'admin' || $this->creator_id === $user->id),
                'can_publish' => !$isStudent && ($user->role === 'admin' || $this->creator_id === $user->id),
                'is_owner' => $this->creator_id === $user->id,
            ],

            // Display helpers for UI
            'display' => [
                'difficulty_badge' => [
                    'text' => ucfirst($this->difficulty),
                    'color' => $this->getDifficultyColor(),
                ],
                'status_badge' => [
                    'text' => $this->is_published ? 'Published' : 'Draft',
                    'color' => $this->is_published ? 'green' : 'yellow',
                ],
                'test_type_badge' => [
                    'text' => ucfirst($this->test_type),
                    'color' => $this->test_type === 'academic' ? 'blue' : 'purple',
                ],
            ],
        ];
    }

    /**
     * Get difficulty color for UI display.
     */
    private function getDifficultyColor(): string
    {
        return match ($this->difficulty) {
            'beginner' => 'green',
            'intermediate' => 'yellow',
            'advanced' => 'red',
            default => 'gray',
        };
    }
}