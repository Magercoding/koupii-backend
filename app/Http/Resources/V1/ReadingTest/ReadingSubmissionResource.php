<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'student_id' => $this->student_id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'time_taken_seconds' => $this->time_taken_seconds,
            'total_score' => $this->total_score,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'total_correct' => $this->total_correct,
            'total_incorrect' => $this->total_incorrect,
            'total_unanswered' => $this->total_unanswered,
            'can_retake' => $this->canRetake(),
            'is_completed' => $this->isCompleted(),

            // Test information
            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'title' => $this->test->title,
                    'description' => $this->test->description,
                    'difficulty' => $this->test->difficulty,
                    'type' => $this->test->type,
                    'timer_mode' => $this->test->timer_mode,
                    'timer_settings' => $this->test->timer_settings,
                    'allow_repetition' => $this->test->allow_repetition,
                    'max_repetition_count' => $this->test->max_repetition_count,
                ];
            }),

            // Student information
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'email' => $this->student->email,
                ];
            }),

            // Answers (for detailed view)
            'answers' => ReadingQuestionAnswerResource::collection($this->whenLoaded('answers')),

            // Vocabulary discoveries
            'vocabulary_discoveries' => VocabularyDiscoveryResource::collection($this->whenLoaded('vocabularyDiscoveries')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}