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
                // Match by UUID or by index_id (fallback for legacy/discover)
                $matchedQuestion = collect($unifiedQuestions)->first(function ($q) use ($recording) {
                    return (($q['id'] ?? null) == $recording->question_id) || 
                           (($q['index_id'] ?? null) == $recording->question_id);
                });

                return [
                    'id' => $recording->id,
                    'question_id' => $recording->question_id,
                    'audio_url' => $recording->id
                        ? url("/api/v1/speaking/recordings/{$recording->id}/stream")
                        : null,
                    'duration_seconds' => $recording->duration_seconds,
                    'duration' => $recording->duration_seconds,
                    'transcript' => $recording->transcript,
                    'confidence_score' => $recording->confidence_score,
                    'fluency_score' => $recording->fluency_score,
                    'speaking_rate' => $recording->speaking_rate,
                    'pause_analysis' => $recording->pause_analysis,
                    'question' => $matchedQuestion,
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
        $questions = collect();

        // 1. Check if linked to a Global Test (Discover Test)
        if ($this->speakingTask && $this->speakingTask->test) {
            $test = $this->speakingTask->test;
            if ($test->passages) {
                foreach ($test->passages as $passageIndex => $passage) {
                    foreach ($passage->questionGroups as $groupIndex => $group) {
                        foreach ($group->questions as $questionIndex => $question) {
                            $questions->push([
                                'id' => $question->id,
                                'index_id' => "{$passageIndex}-{$questionIndex}",
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

        // 2. Regular SpeakingTask questions (can be section-based or flat)
        if ($this->speakingTask && $this->speakingTask->questions) {
            $rawQuestions = is_string($this->speakingTask->questions) 
                ? json_decode($this->speakingTask->questions, true) 
                : $this->speakingTask->questions;

            // Normalize into a flat list with index_id
            if (isset($rawQuestions[0]) && isset($rawQuestions[0]['questions'])) {
                // Section-based
                foreach ($rawQuestions as $sectionIndex => $section) {
                    $sectionQuestions = $section['questions'] ?? [];
                    foreach ($sectionQuestions as $questionIndex => $q) {
                        $questions->push([
                            'id' => $q['id'] ?? null,
                            'index_id' => "{$sectionIndex}-{$questionIndex}",
                            'prompt' => $q['prompt'] ?? ($q['question_text'] ?? ''),
                            'topic' => $q['topic'] ?? null,
                            'order_index' => $q['order_index'] ?? null,
                        ]);
                    }
                }
            } else {
                // Flat list
                foreach ($rawQuestions as $index => $q) {
                    $questions->push([
                        'id' => $q['id'] ?? null,
                        'index_id' => "0-{$index}",
                        'prompt' => $q['prompt'] ?? ($q['question_text'] ?? ''),
                        'topic' => $q['topic'] ?? null,
                        'order_index' => $q['order_index'] ?? null,
                    ]);
                }
            }
            
            return $questions->toArray();
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