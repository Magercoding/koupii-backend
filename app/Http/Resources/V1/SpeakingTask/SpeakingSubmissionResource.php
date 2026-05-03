<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unifiedQuestions = $this->getUnifiedQuestions();

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'attempt_number' => $this->attempt_number,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'test_id' => $this->speaking_task_id ?? $this->test_id,
            'speaking_task_id' => $this->speaking_task_id ?? $this->test_id,
            'total_time_seconds' => $this->total_time_seconds,
            'total_time_formatted' => $this->total_time_seconds 
                ? $this->formatTime($this->total_time_seconds) 
                : null,

            'speaking_task' => [
                'id' => $this->speakingTask?->id ?? $this->test?->id,
                'title' => $this->speakingTask?->title ?? $this->test?->title,
                'difficulty_level' => $this->speakingTask?->difficulty_level ?? $this->test?->difficulty_level,
                'questions' => $unifiedQuestions,
            ],

            // Student information
            'student' => [
                'id' => $this->student?->id,
                'name' => $this->student?->name,
                'email' => $this->student?->email,
            ],

            // Recordings
            'recordings' => $this->recordings ? $this->recordings->map(function ($recording) use ($unifiedQuestions) {
                // Try to match the recording to one of our unified questions
                $matchedQuestion = collect($unifiedQuestions)->first(function ($q) use ($recording) {
                    return ($q['id'] ?? null) == $recording->question_id;
                });

                return [
                    'id' => $recording->id,
                    'question_id' => $recording->question_id,
                    'audio_url' => $recording->id
                        ? url("/api/v1/speaking/recordings/{$recording->id}/stream")
                        : null,
                    'duration_seconds' => $recording->duration_seconds,
                    'transcript' => $recording->transcript,
                    'confidence_score' => $recording->confidence_score,
                    'fluency_score' => $recording->fluency_score,
                    'speaking_rate' => $recording->speaking_rate,
                    'pause_analysis' => $recording->pause_analysis,
                    'question' => $matchedQuestion ?: $recording->question,
                    'created_at' => $recording->created_at,
                ];
            }) : [],

            // Review information
            'review' => $this->when(
                isset($this->review) && $this->review,
                function() {
                    return [
                        'id' => $this->review->id,
                        'total_score' => $this->review->total_score,
                        'overall_feedback' => $this->review->overall_feedback,
                        'skill_scores' => $this->review->skill_scores,
                        'reviewed_at' => $this->review->reviewed_at,
                    ];
                }
            ),

            // Progress information
            'progress' => [
                'completed_recordings' => $this->recordings ? $this->recordings->count() : 0,
                'total_questions' => count($unifiedQuestions),
                'is_complete' => in_array($this->status, ['submitted', 'completed', 'reviewed']),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getUnifiedQuestions(): array
    {
        // 1. Check if linked to a Global Test (Discover Test)
        if ($this->speakingTask && $this->speakingTask->test) {
            $test = $this->speakingTask->test;
            $questions = collect();
            
            if ($test->passages) {
                foreach ($test->passages as $passage) {
                    foreach ($passage->questionGroups as $group) {
                        foreach ($group->questions as $question) {
                            $questions->push([
                                'id' => $question->id,
                                'prompt' => $question->question_text,
                                'topic' => $question->question_data['topic'] ?? null,
                                'order_index' => $question->order_index,
                            ]);
                        }
                    }
                }
            }
            
            return $questions->toArray();
        }

        // 2. Fallback to direct test_id if available
        if ($this->test_id && $this->test) {
            $questions = collect();
            foreach ($this->test->passages as $passage) {
                foreach ($passage->questionGroups as $group) {
                    foreach ($group->questions as $question) {
                        $questions->push([
                            'id' => $question->id,
                            'prompt' => $question->question_text,
                            'topic' => $question->question_data['topic'] ?? null,
                            'order_index' => $question->order_index,
                        ]);
                    }
                }
            }
            return $questions->toArray();
        }

        // 3. Regular SpeakingTask questions
        if ($this->speakingTask && $this->speakingTask->questions) {
            return is_string($this->speakingTask->questions) 
                ? json_decode($this->speakingTask->questions, true) 
                : $this->speakingTask->questions;
        }

        return [];
    }

    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}