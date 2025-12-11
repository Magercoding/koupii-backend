<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningVocabularyDiscovery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'test_id',
        'student_id',
        'word',
        'definition',
        'context_sentence',
        'audio_pronunciation_url',
        'part_of_speech',
        'difficulty_level',
        'discovered_at',
        'mastery_level',
        'times_reviewed',
        'is_bookmarked'
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'is_bookmarked' => 'boolean'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Update mastery level based on reviews
    public function updateMasteryLevel(): void
    {
        $masteryLevel = match (true) {
            $this->times_reviewed >= 10 => 'mastered',
            $this->times_reviewed >= 5 => 'familiar',
            $this->times_reviewed >= 2 => 'learning',
            default => 'new'
        };

        $this->update(['mastery_level' => $masteryLevel]);
    }

    // Mark as reviewed
    public function markAsReviewed(): void
    {
        $this->increment('times_reviewed');
        $this->updateMasteryLevel();
    }

    // Toggle bookmark status
    public function toggleBookmark(): void
    {
        $this->update(['is_bookmarked' => !$this->is_bookmarked]);
    }

    // Check if word is mastered
    public function isMastered(): bool
    {
        return $this->mastery_level === 'mastered';
    }

    // Get mastery progress percentage
    public function getMasteryProgressAttribute(): int
    {
        return match ($this->mastery_level) {
            'mastered' => 100,
            'familiar' => 75,
            'learning' => 50,
            'new' => 25,
            default => 0
        };
    }

    // Scope for bookmarked words
    public function scopeBookmarked($query)
    {
        return $query->where('is_bookmarked', true);
    }

    // Scope by mastery level
    public function scopeByMasteryLevel($query, string $level)
    {
        return $query->where('mastery_level', $level);
    }

    // Scope for words that need review
    public function scopeNeedsReview($query)
    {
        return $query->whereIn('mastery_level', ['new', 'learning']);
    }

    // Scope for recent discoveries
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('discovered_at', '>=', now()->subDays($days));
    }
}