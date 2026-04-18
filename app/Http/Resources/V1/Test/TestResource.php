<?php

namespace App\Http\Resources\V1\Test;

use App\Http\Resources\V1\ReadingTest\PassageResource;
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
            'creator_id'           => $get('creator_id'),

            // Only available on full Eloquent models with eager-loaded relations
            'passages' => $isEloquentModel
                ? $this->whenLoaded('passages', fn () => PassageResource::collection($this->passages))
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
                'total_passages' => $isEloquentModel
                    ? ($this->passages_count ?? $this->passages?->count())
                    : 0,
                'total_questions' => ($isEloquentModel && $this->relationLoaded('passages'))
                    ? $this->passages->sum(function ($passage) {
                        return collect($passage->questionGroups ?? [])->sum(function ($group) {
                            return collect($group->questions ?? [])->count();
                        });
                    })
                    : 0,
            ],
        ];
    }
}
