<?php

namespace App\Http\Resources\V1\Listening;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningTestDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'duration_minutes' => $this->duration_minutes,
            'total_questions' => $this->testQuestions()->count(),
            'total_points' => $this->testQuestions()->sum('points'),
            'difficulty_level' => $this->difficulty_level,
            'test_type' => $this->test_type,
            'allow_repetition' => $this->allow_repetition,
            'max_repetition_count' => $this->max_repetition_count,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Test structure
            'passages' => $this->whenLoaded('passages', function () {
                return $this->passages->map(function ($passage) {
                    return [
                        'id' => $passage->id,
                        'title' => $passage->title,
                        'content' => $passage->content,
                        'passage_type' => $passage->passage_type,
                        'difficulty_level' => $passage->difficulty_level,
                        'word_count' => $passage->word_count,
                        'reading_time_minutes' => $passage->reading_time_minutes,
                        'order' => $passage->order,
                        
                        // Audio segments for listening
                        'audio_segments' => $this->whenLoaded('audioSegments', function () use ($passage) {
                            return $passage->audioSegments->map(function ($segment) {
                                return [
                                    'id' => $segment->id,
                                    'audio_url' => $segment->audio_url,
                                    'start_time' => $segment->start_time,
                                    'end_time' => $segment->end_time,
                                    'duration' => $segment->duration,
                                    'transcript' => $segment->transcript,
                                    'order' => $segment->order,
                                    'has_transcript' => !empty($segment->transcript)
                                ];
                            });
                        })
                    ];
                });
            }),
            
            'questions' => $this->whenLoaded('questions', function () {
                return $this->testQuestions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'question_order' => $question->question_order,
                        'points' => $question->points ?? 1,
                        'passage_id' => $question->passage_id,
                        'question_data' => $question->question_data,
                        
                        // Question options for MCQ, multiple select, etc.
                        'options' => $question->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'option_text' => $option->option_text,
                                'order' => $option->order,
                                // Don't expose correct answers in test view
                                'option_data' => $option->option_data
                            ];
                        }),
                        
                        // Question breakdown for grouped questions
                        'breakdown' => $this->when($question->questionBreakdown, function () use ($question) {
                            return [
                                'id' => $question->questionBreakdown->id,
                                'sub_question_text' => $question->questionBreakdown->sub_question_text,
                                'sub_question_order' => $question->questionBreakdown->sub_question_order,
                                'question_group' => [
                                    'id' => $question->questionBreakdown->questionGroup->id,
                                    'group_title' => $question->questionBreakdown->questionGroup->group_title,
                                    'group_instructions' => $question->questionBreakdown->questionGroup->group_instructions,
                                    'group_order' => $question->questionBreakdown->questionGroup->group_order
                                ]
                            ];
                        })
                    ];
                });
            }),
            
            // Test metadata
            'metadata' => [
                'estimated_completion_time' => $this->getEstimatedCompletionTime(),
                'question_types_summary' => $this->getQuestionTypesSummary(),
                'audio_duration_total' => $this->getTotalAudioDuration(),
                'difficulty_distribution' => $this->getDifficultyDistribution(),
                'skill_areas_covered' => $this->getSkillAreasCovered()
            ],
            
            // Test configuration
            'configuration' => [
                'can_pause' => $this->can_pause ?? true,
                'can_replay_audio' => $this->can_replay_audio ?? true,
                'max_audio_replays' => $this->max_audio_replays,
                'show_transcript' => $this->show_transcript ?? false,
                'show_timer' => $this->show_timer ?? true,
                'auto_submit' => $this->auto_submit ?? true,
                'randomize_questions' => $this->randomize_questions ?? false,
                'randomize_options' => $this->randomize_options ?? false
            ]
        ];
    }
    
    /**
     * Get estimated completion time including audio listening
     */
    private function getEstimatedCompletionTime(): int
    {
        $baseDuration = $this->duration_minutes ?? 60;
        $audioDuration = $this->getTotalAudioDuration();
        
        // Add 50% of audio duration for replay time
        $estimatedAudioTime = ($audioDuration / 60) * 1.5;
        
        return (int) ceil($baseDuration + $estimatedAudioTime);
    }
    
    /**
     * Get summary of question types in the test
     */
    private function getQuestionTypesSummary(): array
    {
        if (!$this->relationLoaded('testQuestions')) {
            return [];
        }
        
        return $this->testQuestions
            ->groupBy('question_type')
            ->map(function ($questions, $type) {
                return [
                    'type' => $type,
                    'count' => $questions->count(),
                    'total_points' => $questions->sum('points')
                ];
            })
            ->values()
            ->toArray();
    }
    
    /**
     * Get total audio duration in minutes
     */
    private function getTotalAudioDuration(): float
    {
        if (!$this->relationLoaded('passages')) {
            return 0;
        }
        
        return $this->passages->sum(function ($passage) {
            if ($passage->relationLoaded('audioSegments')) {
                return $passage->audioSegments->sum('duration');
            }
            return 0;
        }) / 60; // Convert to minutes
    }
    
    /**
     * Get difficulty distribution
     */
    private function getDifficultyDistribution(): array
    {
        if (!$this->relationLoaded('testQuestions')) {
            return [];
        }
        
        return $this->testQuestions
            ->groupBy('difficulty_level')
            ->map(function ($questions) {
                return $questions->count();
            })
            ->toArray();
    }
    
    /**
     * Get skill areas covered by this test
     */
    private function getSkillAreasCovered(): array
    {
        $skillAreas = [];
        
        if (!$this->relationLoaded('testQuestions')) {
            return $skillAreas;
        }
        
        $questionTypes = $this->testQuestions->pluck('question_type')->unique();
        
        foreach ($questionTypes as $type) {
            switch ($type) {
                case 'multiple_choice':
                case 'multiple_select':
                    $skillAreas[] = 'Detail comprehension';
                    break;
                case 'true_false':
                    $skillAreas[] = 'Fact verification';
                    break;
                case 'fill_blank':
                case 'gap_fill_dropdown':
                    $skillAreas[] = 'Specific information extraction';
                    break;
                case 'match_headings':
                    $skillAreas[] = 'Main idea identification';
                    break;
                case 'summary_completion':
                case 'note_completion':
                    $skillAreas[] = 'Key information synthesis';
                    break;
                case 'table_completion':
                    $skillAreas[] = 'Structured information processing';
                    break;
                case 'sentence_completion':
                    $skillAreas[] = 'Context understanding';
                    break;
            }
        }
        
        return array_unique($skillAreas);
    }
}