<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningVocabularyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_id' => $this->test_id,
            'student_id' => $this->student_id,
            'word' => $this->word,
            'definition' => $this->definition,
            'context_sentence' => $this->context_sentence,
            'audio_pronunciation_url' => $this->audio_pronunciation_url,
            'part_of_speech' => $this->part_of_speech,
            'difficulty_level' => $this->difficulty_level,
            'discovered_at' => $this->discovered_at->toISOString(),
            'discovered_at_formatted' => $this->discovered_at->format('M j, Y'),
            'mastery_level' => $this->mastery_level,
            'mastery_progress' => $this->mastery_progress,
            'times_reviewed' => $this->times_reviewed,
            'is_bookmarked' => $this->is_bookmarked,
            'is_mastered' => $this->isMastered(),

            // Include test information when loaded
            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'title' => $this->test->title,
                    'description' => $this->test->description
                ];
            }),

            // Include student information when loaded  
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name
                ];
            }),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString()
        ];
    }
}