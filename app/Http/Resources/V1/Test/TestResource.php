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
        // When the resource is a plain stdClass (from DB::table UNION), cast to array
        // so property access doesn't throw "Undefined property" errors.
        $isStd = is_object($this->resource)
            && !($this->resource instanceof \Illuminate\Database\Eloquent\Model);
        $row = $isStd ? (array) $this->resource : null;

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
            'timer_mode'           => $get('timer_mode'),
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
            'passages' => $this->whenLoaded('passages', fn () =>
                PassageResource::collection($this->passages)
            ),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id'    => $this->creator->id,
                'name'  => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'class' => $this->whenLoaded('class', fn () => [
                'id'         => $this->class->id,
                'name'       => $this->class->name,
                'class_code' => $this->class->class_code,
            ]),

            'statistics' => [
                'total_passages' => $get('passages_count'),
            ],
        ];
    }
}
