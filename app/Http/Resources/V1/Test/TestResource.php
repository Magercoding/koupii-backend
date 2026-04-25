<?php

namespace App\Http\Resources\V1\Test;

use App\Http\Resources\V1\ReadingTest\PassageResource;
use App\Http\Resources\V1\SpeakingTask\SpeakingSectionResource;
use App\Http\Resources\V1\WritingTask\WritingTaskResource;
use App\Http\Resources\V1\Listening\ListeningTaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Supports both Eloquent models and stdClass rows from raw UNION queries.
     */
    public function toArray(Request $request): array
    {
        $isEloquentModel = $this->resource instanceof \Illuminate\Database\Eloquent\Model;
        // When the resource is a plain stdClass (from DB::table UNION), cast to array.
        $row = (!$isEloquentModel && is_object($this->resource)) ? (array) $this->resource : null;

        $get = function (string $key, $default = null) use ($row) {
            if ($row !== null) {
                return array_key_exists($key, $row) ? $row[$key] : $default;
            }
            return $this->{$key} ?? $default;
        };

        return [
            'id'                   => $get('id'),
            'title'                => $get('title'),
            'name'                 => $get('title'), // Alias for frontend
            'cover_image'          => $get('cover_image'),
            'image'                => $get('cover_image'), // Alias for frontend
            'description'          => $get('description'),
            'type'                 => $get('type'),
            'difficulty'           => $get('difficulty'),
            'test_type'            => $get('test_type', $get('type')),
            'timer_mode'           => $get('timer_mode', 'none'),
            'timer_settings'       => $get('timer_settings'),
            'allow_repetition'     => $get('allow_repetition', false),
            'max_repetition_count' => $get('max_repetition_count'),
            'is_public'            => $get('is_public', false),
            'is_published'         => $get('is_published', false),
            'settings'             => $get('settings'),
            'created_at'           => $get('created_at'),
            'updated_at'           => $get('updated_at'),
            'class_id'             => $get('class_id'),
            'class_name'           => $get('class_name'),
            'creator_id'           => $get('creator_id'),
            'attempts_count'       => (int) ($get('attempts_count') ?? ($get('r_count', 0) + $get('l_count', 0) + $get('w_count', 0) + $get('s_count', 0))),
            'attempts'             => (int) ($get('attempts_count') ?? ($get('r_count', 0) + $get('l_count', 0) + $get('w_count', 0) + $get('s_count', 0))), // Alias for frontend

            // Only available on full Eloquent models with eager-loaded relations
            'passages' => $isEloquentModel
                ? $this->whenLoaded('passages', fn () => PassageResource::collection($this->passages))
                : [],
            'speaking_sections' => $isEloquentModel
                ? $this->whenLoaded('speakingSections', fn () => SpeakingSectionResource::collection($this->speakingSections))
                : [],
            'listening_tasks' => $isEloquentModel
                ? $this->whenLoaded('listeningTasks', fn () => ListeningTaskResource::collection($this->listeningTasks))
                : [],
            'writing_tasks' => $isEloquentModel
                ? $this->whenLoaded('writingTasks', fn () => WritingTaskResource::collection($this->writingTasks))
                : [],
            'creator' => $isEloquentModel
                ? $this->whenLoaded('creator', fn () => [
                    'id'    => $this->creator->id,
                    'name'  => $this->creator->name,
                    'email' => $this->creator->email,
                ])
                : null,
            'class' => $isEloquentModel
                ? $this->whenLoaded('class', fn () => [
                    'id'         => $this->class->id,
                    'name'       => $this->class->name,
                    'class_code' => $this->class->class_code,
                ])
                : null,

            'statistics' => [
                'total_items' => $isEloquentModel
                    ? match($this->type) {
                        'reading'   => (int) ($this->passages_count ?? ($this->relationLoaded('passages') ? $this->passages->count() : 0)),
                        'listening' => (int) ($this->listening_tasks_count ?? ($this->relationLoaded('listeningTasks') ? $this->listeningTasks->count() : 0)),
                        'writing'   => (int) ($this->writing_tasks_count ?? ($this->relationLoaded('writingTasks') ? $this->writingTasks->count() : 0)),
                        'speaking'  => (int) ($this->speaking_sections_count ?? ($this->relationLoaded('speakingSections') ? $this->speakingSections->count() : 0)),
                        default => 0,
                    }
                    : 0,
                'total_questions' => ($isEloquentModel)
                    ? match($this->type) {
                        'reading' => $this->relationLoaded('passages') 
                            ? $this->passages->sum(fn($p) => collect($p->questionGroups ?? [])->sum(fn($g) => collect($g->questions ?? [])->count())) 
                            : 0,
                        'listening' => $this->relationLoaded('listeningTasks') 
                            ? $this->listeningTasks->sum(fn($t) => collect($t->questions ?? [])->count()) 
                            : 0,
                        'writing' => $this->relationLoaded('writingTasks') 
                            ? $this->writingTasks->sum(fn($t) => is_array($t->questions) ? count($t->questions) : (collect($t->taskQuestions ?? [])->count() ?: 1)) 
                            : 0,
                        'speaking' => $this->relationLoaded('speakingSections')
                            ? $this->speakingSections->sum(fn($s) => collect($s->topics ?? [])->sum(fn($t) => collect($t->questions ?? [])->count()))
                            : 0,
                        default => 0,
                    }
                    : 0,
            ],
        ];
    }
}
