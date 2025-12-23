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
            'total_time_seconds' => $this->total_time_seconds,
            'total_time_formatted' => $this->total_time_seconds 
                ? $this->formatTime($this->total_time_seconds) 
                : null,

            // Assignment information
            'assignment' => [
                'id' => $this->assignment->id,
                'due_date' => $this->assignment->due_date,
                'test' => [
                    'id' => $this->assignment->test->id,
                    'title' => $this->assignment->test->title,
                    'description' => $this->assignment->test->description,
                ],
                'class' => [
                    'id' => $this->assignment->class->id,
                    'name' => $this->assignment->class->name,
                ],
            ],

            // Student information
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
            ],

            // Recordings with speech analysis
            'recordings' => $this->recordings->map(function ($recording) {
                return [
                    'id' => $recording->id,
                    'question_id' => $recording->question_id,
                    'file_path' => $recording->file_path,
                    'file_name' => $recording->file_name,
                    'file_url' => $recording->file_url ?? null,
                    'duration_seconds' => $recording->duration_seconds,
                    'duration_formatted' => $recording->duration_seconds
                        ? $this->formatTime($recording->duration_seconds)
                        : null,
                    'file_size' => $recording->file_size,
                    'file_size_formatted' => $recording->file_size
                        ? $this->formatFileSize($recording->file_size)
                        : null,
                    
                    // Speech-to-text analysis
                    'transcript' => $recording->transcript,
                    'confidence_score' => $recording->confidence_score,
                    'fluency_score' => $recording->fluency_score,
                    'speaking_rate' => $recording->speaking_rate,
                    'pause_analysis' => $recording->pause_analysis,
                    
                    // Question information
                    'question' => $recording->question ? [
                        'id' => $recording->question->id,
                        'topic' => $recording->question->topic,
                        'prompt' => $recording->question->prompt,
                        'preparation_time_seconds' => $recording->question->preparation_time_seconds,
                        'response_time_seconds' => $recording->question->response_time_seconds,
                    ] : null,

                    'created_at' => $recording->created_at,
                    'updated_at' => $recording->updated_at,
                ];
            }),

            // Review information
            'review' => $this->when(
                isset($this->review),
                [
                    'id' => $this->review->id,
                    'overall_score' => $this->review->overall_score,
                    'pronunciation_score' => $this->review->pronunciation_score,
                    'fluency_score' => $this->review->fluency_score,
                    'grammar_score' => $this->review->grammar_score,
                    'vocabulary_score' => $this->review->vocabulary_score,
                    'content_score' => $this->review->content_score,
                    'feedback' => $this->review->feedback,
                    'detailed_comments' => $this->review->detailed_comments,
                    'reviewed_at' => $this->review->reviewed_at,
                    'reviewed_by' => $this->review->reviewedBy ? [
                        'id' => $this->review->reviewedBy->id,
                        'name' => $this->review->reviewedBy->name,
                    ] : null,
                ]
            ),

            // Calculated metrics
            'speech_analysis_summary' => $this->when(
                $this->recordings->isNotEmpty(),
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
                'completed_recordings' => $this->recordings->count(),
                'total_questions' => $this->getTotalQuestions(),
                'completion_percentage' => $this->getCompletionPercentage(),
                'is_complete' => $this->status === 'submitted' || $this->status === 'reviewed',
            ],

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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
        return $this->recordings->sum(function ($recording) {
            return $recording->transcript 
                ? str_word_count($recording->transcript) 
                : 0;
        });
    }

    private function getTotalQuestions(): int
    {
        return $this->assignment->test->sections->sum(function ($section) {
            return $section->questions->count();
        });
    }

    private function getCompletionPercentage(): float
    {
        $totalQuestions = $this->getTotalQuestions();
        if ($totalQuestions === 0) {
            return 0.0;
        }

        return round(($this->recordings->count() / $totalQuestions) * 100, 2);
    }
}