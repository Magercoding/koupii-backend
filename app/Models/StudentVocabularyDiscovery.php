<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentVocabularyDiscovery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'test_id',
        'vocabulary_id',
        'discovered_at',
        'is_saved'
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'is_saved' => 'boolean'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }

    // Save vocabulary to student's bank
    public function saveToBank(): StudentVocabularyBank
    {
        $bankEntry = StudentVocabularyBank::firstOrCreate([
            'student_id' => $this->student_id,
            'vocabulary_id' => $this->vocabulary_id,
        ], [
            'discovered_from_test_id' => $this->test_id,
            'is_mastered' => false,
            'practice_count' => 0,
        ]);

        $this->update(['is_saved' => true]);

        return $bankEntry;
    }
}