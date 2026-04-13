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
    
    public const STATUS_TO_DO = 'to_do';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REVIEWED = 'reviewed';

    protected $fillable = [
        'speaking_task_id',
        'student_id',
        'assignment_id',
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

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function studentAssignment(): BelongsTo
    {
        return $this->belongsTo(StudentAssignment::class, 'assignment_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function speakingTask(): BelongsTo
    {
        return $this->belongsTo(SpeakingTask::class, 'speaking_task_id');
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