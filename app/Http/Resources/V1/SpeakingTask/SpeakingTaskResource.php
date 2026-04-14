<?php

namespace App\Http\Resources\V1\SpeakingTask;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'description'        => $this->description,
            'instructions'       => $this->instructions,
            'difficulty_level'   => $this->difficulty_level,
            'difficulty'         => $this->difficulty_level,
            'time_limit_seconds' => $this->time_limit_seconds,
            'topic'              => $this->topic,
            'situation_context'  => $this->situation_context,
            'questions'          => $this->questions,
            'passages'           => $this->questions,
            'sample_audio'       => $this->sample_audio,
            'rubric'             => $this->rubric,
            'is_published'       => $this->is_published,
            'creator_id'         => $this->created_by,
            'creator'            => $this->whenLoaded('creator', function () {
                return [
                    'id'    => $this->creator->id,
                    'name'  => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            // CRUD permission flags for frontend
            'can_edit'   => $user && ($user->role === 'admin' || ($user->role === 'teacher' && $this->created_by === $user->id)),
            'can_delete' => $user && ($user->role === 'admin' || ($user->role === 'teacher' && $this->created_by === $user->id)),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
