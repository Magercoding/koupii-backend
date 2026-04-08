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
            'assignment_id' => $this->assignment_id,
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
                $isStudent = auth()->user()->role === 'student';
                $canSeeAnswers = !$isStudent || in_array($this->status, ['submitted', 'completed', 'reviewed']);
                
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
                    'passages' => PassageResource::collection($this->test->passages)
                        ->additional(['canSeeAnswers' => $canSeeAnswers]),
                ];
            }),

            'reading_task' => $this->whenLoaded('readingTask', function () {
                $isStudent = auth()->user()->role === 'student';
                $canSeeAnswers = !$isStudent || in_array($this->status, ['submitted', 'completed', 'reviewed']);
                
                $passages = collect($this->readingTask->passages)->map(function ($passage) use ($canSeeAnswers) {
                    if (isset($passage['question_groups'])) {
                        $groups = collect($passage['question_groups'])->map(function ($group) use ($canSeeAnswers) {
                            if (isset($group['questions'])) {
                                $group['questions'] = collect($group['questions'])->map(function ($q) use ($canSeeAnswers) {
                                    if (!$canSeeAnswers) {
                                        unset($q['correct_answers']);
                                        unset($q['correct_answer']);
                                    }
                                    return $q;
                                })->toArray();
                            }
                            return $group;
                        })->toArray();
                        
                        $passage['questionGroups'] = $groups;
                        unset($passage['question_groups']);
                    }
                    return $passage;
                })->toArray();

                return [
                    'id' => $this->readingTask->id,
                    'title' => $this->readingTask->title,
                    'description' => $this->readingTask->description,
                    'difficulty' => $this->readingTask->difficulty,
                    'task_type' => $this->readingTask->task_type,
                    'timer_type' => $this->readingTask->timer_type,
                    'time_limit_seconds' => $this->readingTask->time_limit_seconds,
                    'allow_retake' => $this->readingTask->allow_retake,
                    'max_retake_attempts' => $this->readingTask->max_retake_attempts,
                    'passages' => $passages,
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