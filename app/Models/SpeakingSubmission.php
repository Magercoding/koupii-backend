<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpeakingSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'test_id',
        'student_id',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'total_time_seconds'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(SpeakingRecording::class, 'submission_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(SpeakingReview::class, 'submission_id');
    }
}