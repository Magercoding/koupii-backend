<?php

namespace App\Http\Resources\V1\ReadingTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isStudent = $this->additional['isStudent'] ?? false;

        $data = [
            'id' => $this->id,
            'type' => $this->question_type,
            'number' => $this->question_number,
            'text' => $this->question_text,
            'options' => $this->options->map(fn($opt) => [
                'key' => $opt->option_key,
                'text' => $opt->option_text,
            ]),
        ];

        if (!$isStudent) {
            $data['correct_answers'] = json_decode($this->correct_answers, true) ?? [];
            $data['breakdowns'] = $this->breakdowns->map(fn($bd) => [
                'explanation' => $bd->explanation,
                'highlights' => $bd->highlightSegments->map(fn($h) => [
                    'start' => $h->start_char_index,
                    'end' => $h->end_char_index,
                ]),
            ]);
        }

        return $data;
    
    }
}
