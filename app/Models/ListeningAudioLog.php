<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningAudioLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'submission_id',
        'question_id',
        'segment_id',
        'action_type',
        'timestamp_seconds',
        'duration_listened',
        'playback_speed',
        'device_info',
        'logged_at'
    ];

    protected $casts = [
        'timestamp_seconds' => 'decimal:2',
        'duration_listened' => 'decimal:2',
        'playback_speed' => 'decimal:2',
        'device_info' => 'array',
        'logged_at' => 'datetime'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ListeningSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }

    public function audioSegment(): BelongsTo
    {
        return $this->belongsTo(ListeningAudioSegment::class, 'segment_id');
    }

    // Get formatted timestamp
    public function getFormattedTimestampAttribute(): string
    {
        if (is_null($this->timestamp_seconds)) {
            return '00:00';
        }

        $minutes = floor($this->timestamp_seconds / 60);
        $seconds = $this->timestamp_seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // Get formatted duration
    public function getFormattedDurationAttribute(): string
    {
        if (is_null($this->duration_listened)) {
            return '00:00';
        }

        $minutes = floor($this->duration_listened / 60);
        $seconds = $this->duration_listened % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // Check if action was a play event
    public function isPlayAction(): bool
    {
        return $this->action_type === 'play';
    }

    // Check if action was a pause event
    public function isPauseAction(): bool
    {
        return $this->action_type === 'pause';
    }

    // Check if action was a seek event
    public function isSeekAction(): bool
    {
        return $this->action_type === 'seek';
    }

    // Check if action was a replay event
    public function isReplayAction(): bool
    {
        return $this->action_type === 'replay';
    }

    // Scope for specific action types
    public function scopeActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    // Scope for recent logs
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    // Scope for specific question
    public function scopeForQuestion($query, string $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    // Scope for specific audio segment
    public function scopeForSegment($query, string $segmentId)
    {
        return $query->where('segment_id', $segmentId);
    }

    // Scope ordered by timestamp
    public function scopeOrderedByTime($query)
    {
        return $query->orderBy('logged_at');
    }
}