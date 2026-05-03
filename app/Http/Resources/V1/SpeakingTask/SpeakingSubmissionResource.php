<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                'questions' => $this->getUnifiedQuestions(),
            ],

            // Assignment information
            'assignment' => $this->whenLoaded('assignment', function () {
                if (!$this->assignment) return null;
                
                return [
                    'id' => $this->assignment->id,
                    'due_date' => $this->assignment->due_date,
                    'title' => $this->assignment->getAssignmentTitle(),
                    'test' => $this->assignment->test ? [
                        'id' => $this->assignment->test->id,
                        'title' => $this->assignment->test->title,
                        'description' => $this->assignment->test->description,
                    ] : null,
                    'task' => [
                        'id' => $this->speakingTask?->id,
                        'title' => $this->speakingTask?->title,
                        'topic' => $this->speakingTask?->topic,
                    ],
                    'class' => $this->assignment->class ? [
                        'id' => $this->assignment->class->id,
                        'name' => $this->assignment->class->name,
                    ] : null,
                ];
            }),

            // Student information
            'student' => [
                'id' => $this->student?->id,
                'name' => $this->student?->name,
                'email' => $this->student?->email,
            ],

            // Recordings with speech analysis
            'recordings' => $this->recordings ? $this->recordings->map(function ($recording) {
                // Try to find question text from Task/Test if not embedded
                $questionData = $this->findQuestionInTask($recording->question_id);

                return [
                    'id' => $recording->id,
                    'question_id' => $recording->question_id,
                    'audio_file_path' => $recording->audio_file_path,
                    'audio_url' => $recording->id
                        ? url("/api/v1/speaking/recordings/{$recording->id}/stream")
                        : null,
                    'duration_seconds' => $recording->duration_seconds,
                    'duration_formatted' => $recording->duration_seconds
                        ? $this->formatTime($recording->duration_seconds)
                        : null,
                    'file_size' => null,
                    'file_size_formatted' => null,
                    
                    // Speech-to-text analysis
                    'transcript' => $recording->transcript,
                    'confidence_score' => $recording->confidence_score,
                    'fluency_score' => $recording->fluency_score,
                    'speaking_rate' => $recording->speaking_rate,
                    'pause_analysis' => $recording->pause_analysis,
                    
                    // Question information
                    'question' => $questionData,

                    'created_at' => $recording->created_at,
                    'updated_at' => $recording->updated_at,
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
                        'question_scores' => $this->review->question_scores,
                        'reviewed_at' => $this->review->reviewed_at,
                        'teacher' => $this->review->teacher ? [
                            'id' => $this->review->teacher->id,
                            'name' => $this->review->teacher->name,
                        ] : null,
                    ];
                }
            ),

            // Calculated metrics
            'speech_analysis_summary' => $this->when(
                $this->recordings && $this->recordings->isNotEmpty(),
                [
                    'total_speaking_time' => $this->recordings->sum('duration_seconds'),
                    'average_confidence' => $this->recordings->avg('confidence_score'),
                    'average_fluency' => $this->recordings->avg('fluency_score'),
                    'average_speaking_rate' => $this->recordings->avg('speaking_rate'),
                    'total_words' => $this->getTotalWordCount(),
                    'questions_completed' => $this->recordings->count(),
                    'has_transcript' => $this->recordings->where('transcript', '!=', null)->isNotEmpty(),
                ]
            ),

            // Progress information
            'progress' => [
                'completed_recordings' => $this->recordings ? $this->recordings->count() : 0,
                'total_questions' => $this->getTotalQuestions(),
                'completion_percentage' => $this->getCompletionPercentage(),
                'is_complete' => $this->status === 'submitted' || $this->status === 'reviewed',
            ],

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getUnifiedQuestions(): array
    {
        $questions = [];

        // Scenario 1: SpeakingTask model (Assignment)
        if ($this->speakingTask && is_array($this->speakingTask->questions)) {
             // Handle both flattened array and section-based structure
             $qArray = $this->speakingTask->questions;
             if (isset($qArray[0]) && isset($qArray[0]['questions'])) {
                 // Section based
                 foreach ($qArray as $section) {
                     foreach ($section['questions'] as $q) {
                         $questions[] = $q;
                     }
                 }
             } else {
                 $questions = $qArray;
             }
        } 
        // Scenario 2: Global Test model (Discover Test)
        elseif ($this->test) {
            $this->test->loadMissing('sections.questions');
            foreach ($this->test->sections as $section) {
                foreach ($section->questions as $q) {
                    $questions[] = [
                        'id' => $q->id,
                        'prompt' => $q->prompt,
                        'topic' => $q->topic ?? $section->title,
                        'preparation_time_seconds' => $q->preparation_time_seconds,
                        'response_time_seconds' => $q->response_time_seconds,
                    ];
                }
            }
        }

        return $questions;
    }

    private function findQuestionInTask(?string $questionId): ?array
    {
        if (!$questionId) return null;

        $allQuestions = $this->getUnifiedQuestions();
        foreach ($allQuestions as $q) {
            if ($q['id'] == $questionId) {
                return $q;
            }
        }

        return null;
    }

    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getTotalWordCount(): int
    {
        if (!$this->recordings) return 0;

        return $this->recordings->sum(function ($recording) {
            return $recording->transcript 
                ? str_word_count($recording->transcript) 
                : 0;
        });
    }

    private function getTotalQuestions(): int
    {
        return count($this->getUnifiedQuestions());
    }

    private function getCompletionPercentage(): float
    {
        $totalQuestions = $this->getTotalQuestions();
        if ($totalQuestions === 0) {
            return 0.0;
        }

        $completedCount = $this->recordings ? $this->recordings->count() : 0;
        return round(($completedCount / $totalQuestions) * 100, 2);
    }

}