<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SpeakingRecording extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'speaking_recordings';
    protected $fillable = [
        'submission_id',
        'question_id',
        'audio_file_path',
        'duration_seconds',
        'recording_started_at',
        'recording_ended_at',
        'transcript',
        'confidence_score',
        'word_count',
        'speaking_rate',
        'fluency_score',
        'pause_analysis',
        'speech_processed',
        'speech_processed_at'
    ];

    protected $casts = [
        'recording_started_at' => 'datetime',
        'recording_ended_at' => 'datetime',
        'confidence_score' => 'decimal:4',
        'speaking_rate' => 'decimal:2',
        'fluency_score' => 'decimal:2',
        'pause_analysis' => 'array',
        'speech_processed' => 'boolean',
        'speech_processed_at' => 'datetime'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SpeakingSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SpeakingQuestion::class, 'question_id');
    }

    // Accessor to get full audio URL
    public function getAudioUrlAttribute(): ?string
    {
        if (!$this->audio_file_path) {
            return null;
        }

        return Storage::disk('speaking_recordings')->url($this->audio_file_path);
    }

    // Check if audio file exists
    public function audioFileExists(): bool
    {
        if (!$this->audio_file_path) {
            return false;
        }

        return Storage::disk('speaking_recordings')->exists($this->audio_file_path);
    }

    // Delete audio file from storage
    public function deleteAudioFile(): bool
    {
        if (!$this->audio_file_path) {
            return true;
        }

        return Storage::disk('speaking_recordings')->delete($this->audio_file_path);
    }

    // Format duration for display
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '00:00';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // Model events
    protected static function booted(): void
    {
        // Delete audio file when model is deleted
        static::deleting(function (SpeakingRecording $recording) {
            $recording->deleteAudioFile();
        });
    }
}