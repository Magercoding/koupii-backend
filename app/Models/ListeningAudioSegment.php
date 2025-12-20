<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningAudioSegment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'question_id',
        'segment_type',
        'audio_url',
        'transcript_text',
        'start_time_seconds',
        'end_time_seconds',
        'explanation_text',
        'vocabulary_notes',
        'display_order'
    ];

    protected $casts = [
        'start_time_seconds' => 'decimal:2',
        'end_time_seconds' => 'decimal:2',
        'vocabulary_notes' => 'array'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }

    // Get duration of audio segment
    public function getDurationAttribute(): float
    {
        if ($this->end_time_seconds && $this->start_time_seconds) {
            return $this->end_time_seconds - $this->start_time_seconds;
        }
        return 0;
    }

    // Check if segment has transcript
    public function hasTranscript(): bool
    {
        return !empty($this->transcript_text);
    }

    // Check if segment has vocabulary notes
    public function hasVocabularyNotes(): bool
    {
        return !empty($this->vocabulary_notes);
    }

    // Get formatted time range
    public function getTimeRangeAttribute(): string
    {
        $start = $this->formatTime($this->start_time_seconds);
        $end = $this->formatTime($this->end_time_seconds);
        return "{$start} - {$end}";
    }

    // Format time in MM:SS format
    private function formatTime(?float $seconds): string
    {
        if (is_null($seconds)) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    // Scope for main audio segments
    public function scopeMainAudio($query)
    {
        return $query->where('segment_type', 'main_audio');
    }

    // Scope for explanation segments
    public function scopeExplanation($query)
    {
        return $query->where('segment_type', 'explanation');
    }

    // Scope for pronunciation segments
    public function scopePronunciation($query)
    {
        return $query->where('segment_type', 'pronunciation');
    }

    // Scope ordered by display order
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}