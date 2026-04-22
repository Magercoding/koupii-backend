<?php

namespace App\Http\Resources\V1\ReadingTest;

use App\Models\Test;
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
                // ISO-8601 UTC so browsers parse one unambiguous instant (avoids countdown stuck at 00:00:00).
                'started_at' => $this->started_at?->utc()->toIso8601String(),
                'submitted_at' => $this->submitted_at?->utc()->toIso8601String(),
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
                    if (!$this->test) return null;

                    $user = auth()->user();
                    $isStudent = $user && $user->role === 'student';
                    $canSeeAnswers = !$isStudent || in_array($this->status, ['submitted', 'completed', 'reviewed']);

                    $timerSettings = self::coerceTimerSettingsArray($this->test->timer_settings);

                    return [
                        'id' => $this->test->id,
                        'title' => $this->test->title,
                        'description' => $this->test->description,
                        'difficulty' => $this->test->difficulty,
                        'type' => $this->test->type,
                        'timer_mode' => $this->test->timer_mode,
                        'timer_settings' => $timerSettings !== [] ? $timerSettings : $this->test->timer_settings,
                        'time_limit_seconds' => self::resolveTimerSettingsTotalSeconds($timerSettings),
                        'allow_repetition' => $this->test->allow_repetition,
                        'max_repetition_count' => $this->test->max_repetition_count,
                        'passages' => PassageResource::collection($this->test->passages)
                            ->additional(['canSeeAnswers' => $canSeeAnswers]),
                    ];
                }),

                'reading_task' => $this->whenLoaded('readingTask', function () {
                    if (!$this->readingTask) return null;

                    $user = auth()->user();
                    $isStudent = $user && $user->role === 'student';
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

                    $resolvedLimit = (int) ($this->readingTask->time_limit_seconds ?? 0);
                    $outTimerType = $this->readingTask->timer_type;
                    if ($resolvedLimit <= 0 && $this->readingTask->test_id) {
                        $linked = Test::query()->find($this->readingTask->test_id);
                        if ($linked) {
                            $fromTs = self::resolveTimerSettingsTotalSeconds(
                                self::coerceTimerSettingsArray($linked->timer_settings)
                            );
                            if ($fromTs !== null) {
                                $resolvedLimit = $fromTs;
                            }
                            $tt = (string) ($this->readingTask->timer_type ?? '');
                            if (($tt === '' || $tt === 'none') && $linked->timer_mode) {
                                $outTimerType = $linked->timer_mode;
                            }
                        }
                    }
                    $suggestMin = (int) ($this->readingTask->suggest_time_minutes ?? 0);
                    if ($resolvedLimit <= 0 && $suggestMin > 0) {
                        $resolvedLimit = $suggestMin * 60;
                    }

                    return [
                        'id' => $this->readingTask->id,
                        'title' => $this->readingTask->title,
                        'description' => $this->readingTask->description,
                        'difficulty' => $this->readingTask->difficulty,
                        'task_type' => $this->readingTask->task_type,
                        'timer_type' => $outTimerType,
                        'time_limit_seconds' => $resolvedLimit > 0 ? $resolvedLimit : $this->readingTask->time_limit_seconds,
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

    /**
     * @return array<string, mixed>
     */
    private static function coerceTimerSettingsArray(mixed $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (is_object($raw)) {
            $decoded = json_decode(json_encode($raw), true);

            return is_array($decoded) ? $decoded : [];
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $timerSettings
     */
    private static function resolveTimerSettingsTotalSeconds(array $timerSettings): ?int
    {
        if ($timerSettings === []) {
            return null;
        }

        if (isset($timerSettings['time_limit']) && is_numeric($timerSettings['time_limit'])) {
            $v = max(0, (int) $timerSettings['time_limit']);

            return $v > 0 ? $v : null;
        }

        if (isset($timerSettings['time_limit_seconds']) && is_numeric($timerSettings['time_limit_seconds'])) {
            $v = max(0, (int) $timerSettings['time_limit_seconds']);

            return $v > 0 ? $v : null;
        }

        $h = (int) ($timerSettings['hours'] ?? 0);
        $m = (int) ($timerSettings['minutes'] ?? 0);
        $s = (int) ($timerSettings['seconds'] ?? 0);
        $total = max(0, ($h * 3600) + ($m * 60) + $s);

        return $total > 0 ? $total : null;
    }
}