<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $writing_task_id
 * @property string $student_id
 * @property string|null $content
 * @property array|null $files
 * @property int|null $word_count
 * @property string $status
 * @property int $attempt_number
 * @property int|null $time_taken_seconds
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_submissions';
    public $incrementing = false;
    protected $keyType = 'string';

    const STATUS_TODO = 'to_do';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_DONE = 'done';

    protected $fillable = [
        'writing_task_id',
        'student_id',
        'content',
        'files',
        'word_count',
        'status',
        'attempt_number',
        'time_taken_seconds',
        'submitted_at',
    ];

    protected $casts = [
        'files' => 'array',
        'word_count' => 'integer',
        'attempt_number' => 'integer',
        'time_taken_seconds' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(WritingTask::class, 'writing_task_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviews()
    {
        return $this->hasMany(WritingReview::class, 'submission_id');
    }

    public function latestReview()
    {
        return $this->hasOne(WritingReview::class, 'submission_id')->latestOfMany();
    }

    public function markSubmitted(): self
    {
        $this->status = self::STATUS_SUBMITTED;
        $this->submitted_at = now();
        $this->save();

        return $this;
    }
}