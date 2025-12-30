<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $test_id
 * @property string $task_type
 * @property string|null $title
 * @property string|null $description
 * @property string|null $instructions
 * @property string|null $difficulty
 * @property string $timer_type
 * @property int|null $time_limit_seconds
 * @property bool $allow_retake
 * @property int|null $max_retake_attempts
 * @property array|null $retake_options
 * @property bool $allow_submission_files
 * @property bool $is_published
 * @property string|null $created_by
 * @property array|null $passages
 * @property array|null $passage_images
 * @property int|null $suggest_time_minutes
 * @property string|null $difficulty_level
 * @property array|null $question_types
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReadingTask extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reading_tasks';
    public $incrementing = false;
    protected $keyType = 'string';

    // Timer types
    public const TIMER_TYPES = [
        'none' => 'No Timer',
        'countdown' => 'Countdown Timer',
        'countup' => 'Count Up Timer'
    ];

    // Difficulty levels
    public const DIFFICULTY_LEVELS = [
        'beginner' => 'Beginner',
        'elementary' => 'Elementary',
        'intermediate' => 'Intermediate',
        'upper_intermediate' => 'Upper Intermediate',
        'advanced' => 'Advanced',
        'proficiency' => 'Proficiency'
    ];

    // Retake options
    public const RETAKE_OPTIONS = [
        'repeat_all' => 'Repeat All Questions',
        'focus_mistakes' => 'Focus on Mistake Patterns',
        'choose_questions' => 'Choose Specific Questions'
    ];

    protected $fillable = [
        'test_id',
        'task_type',
        'title',
        'description',
        'instructions',
        'difficulty',
        'timer_type',
        'time_limit_seconds',
        'allow_retake',
        'max_retake_attempts',
        'retake_options',
        'allow_submission_files',
        'is_published',
        'created_by',
        'passages',
        'passage_images',
        'suggest_time_minutes',
        'difficulty_level',
        'question_types',
    ];

    protected $casts = [
        'retake_options' => 'array',
        'allow_retake' => 'boolean',
        'allow_submission_files' => 'boolean',
        'is_published' => 'boolean',
        'passages' => 'array',
        'passage_images' => 'array',
        'question_types' => 'array',
    ];

    /**
     * Get the test that owns the reading task.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get the user who created the reading task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the assignments for the reading task.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ReadingTaskAssignment::class, 'reading_task_id');
    }

    /**
     * Get the submissions for the reading task.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ReadingSubmission::class, 'reading_task_id');
    }

    /**
     * Scope a query to only include published tasks.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include tasks by difficulty.
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Get all difficulty levels as options.
     */
    public static function getDifficultyOptions(): array
    {
        return self::DIFFICULTY_LEVELS;
    }

    /**
     * Get all timer types as options.
     */
    public static function getTimerTypeOptions(): array
    {
        return self::TIMER_TYPES;
    }

    /**
     * Check if the task allows retakes.
     */
    public function allowsRetakes(): bool
    {
        return $this->allow_retake && $this->max_retake_attempts > 0;
    }

    /**
     * Get the total number of questions in all passages.
     */
    public function getTotalQuestionsAttribute(): int
    {
        if (!$this->passages || !is_array($this->passages)) {
            return 0;
        }

        $totalQuestions = 0;
        foreach ($this->passages as $passage) {
            if (isset($passage['question_groups']) && is_array($passage['question_groups'])) {
                foreach ($passage['question_groups'] as $group) {
                    if (isset($group['questions']) && is_array($group['questions'])) {
                        $totalQuestions += count($group['questions']);
                    }
                }
            }
        }

        return $totalQuestions;
    }

    /**
     * Get estimated completion time in minutes.
     */
    public function getEstimatedTimeAttribute(): int
    {
        if ($this->suggest_time_minutes) {
            return $this->suggest_time_minutes;
        }

        // Fallback: estimate based on number of questions (2 minutes per question)
        return $this->getTotalQuestionsAttribute() * 2;
    }

    /**
     * Check if task has timer enabled.
     */
    public function hasTimer(): bool
    {
        return $this->timer_type !== 'none' && $this->time_limit_seconds > 0;
    }

    /**
     * Get formatted timer display.
     */
    public function getFormattedTimerAttribute(): string
    {
        if (!$this->hasTimer()) {
            return 'No time limit';
        }

        $minutes = floor($this->time_limit_seconds / 60);
        $seconds = $this->time_limit_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}