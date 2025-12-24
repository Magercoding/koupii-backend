<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $test_id
 * @property string $task_type
 * @property string|null $topic
 * @property string|null $prompt
 * @property int|null $suggest_time_minutes
 * @property int|null $min_word_count
 * @property string|null $sample_answer
 * @property array|null $images
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingTask extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_tasks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'test_id',
        'task_type',
        'topic',
        'prompt',
        'suggest_time_minutes',
        'min_word_count',
        'sample_answer',
        'images',
        'allow_retake',
        'max_retake_attempts',
        'retake_options',
        'timer_type',
        'time_limit_seconds',
        'allow_submission_files',
        'questions', // New JSON field
        'title',
        'description',
        'instructions',
        'difficulty',
        'word_limit',
        'due_date',
        'is_published',
    ];

    protected $casts = [
        'task_type' => 'string',
        'images' => 'array',
        'questions' => 'array', // New JSON field
        'retake_options' => 'array',
        'is_published' => 'boolean',
        'allow_retake' => 'boolean',
        'allow_submission_files' => 'boolean',
        'due_date' => 'datetime',
    ];
    /**
     * relationships
     */
    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function assignments()
    {
        return $this->hasMany(WritingTaskAssignment::class, 'writing_task_id');
    }

    public function submissions()
    {
        return $this->hasMany(WritingSubmission::class, 'writing_task_id');
    }

    public function questions()
    {
        return $this->hasMany(WritingTaskQuestion::class, 'writing_task_id');
    }
    /**
     * helpers
     */
    public function is_retakable(): bool
    {
        return (bool) $this->allow_retake;
    }
    public const TIMER_NONE = 'none';
    public const TIMER_COUNTDOWN = 'countdown';
    public const TIMER_COUNTUP = 'countup';

    public const TASK_TYPES = ['report', 'essay'];

    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';

    public const DIFFICULTY_LEVELS = [
        self::DIFFICULTY_BEGINNER,
        self::DIFFICULTY_INTERMEDIATE,
        self::DIFFICULTY_ADVANCED,
    ];

    /**
     * Get difficulty label
     */
    public function getDifficultyLabel(): string
    {
        return match($this->difficulty) {
            self::DIFFICULTY_BEGINNER => 'Beginner',
            self::DIFFICULTY_INTERMEDIATE => 'Intermediate',
            self::DIFFICULTY_ADVANCED => 'Advanced',
            default => 'Not Set'
        };
    }

    /**
     * Check if task is published
     */
    public function isPublished(): bool
    {
        return (bool) $this->is_published;
    }

    /**
     * Check if task has deadline passed
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }
}
