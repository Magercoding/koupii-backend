<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentVocabularyBank extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'vocabulary_id',
        'discovered_from_test_id',
        'is_mastered',
        'practice_count',
        'last_practiced_at'
    ];

    protected $casts = [
        'is_mastered' => 'boolean',
        'last_practiced_at' => 'datetime'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }

    public function discoveredFromTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'discovered_from_test_id');
    }

    // Record practice session
    public function practice(): void
    {
        $this->increment('practice_count');
        $this->update(['last_practiced_at' => now()]);

        // Consider mastered after 5 practices
        if ($this->practice_count >= 5 && !$this->is_mastered) {
            $this->update(['is_mastered' => true]);
        }
    }

    // Get mastery level
    public function getMasteryLevelAttribute(): string
    {
        if ($this->is_mastered) {
            return 'mastered';
        }

        return match (true) {
            $this->practice_count >= 3 => 'advanced',
            $this->practice_count >= 1 => 'beginner',
            default => 'new'
        };
    }

    // Scopes
    public function scopeMastered($query)
    {
        return $query->where('is_mastered', true);
    }

    public function scopeNotMastered($query)
    {
        return $query->where('is_mastered', false);
    }

    public function scopeRecentlyPracticed($query, $days = 7)
    {
        return $query->where('last_practiced_at', '>=', now()->subDays($days));
    }
}