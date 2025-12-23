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
 * @property string|null $audio_url
 * @property int|null $audio_duration_seconds
 * @property string|null $transcript
 * @property array|null $audio_segments
 * @property int|null $suggest_time_minutes
 * @property int|null $max_attempts_per_audio
 * @property bool $show_transcript
 * @property bool $allow_replay
 * @property array|null $replay_settings
 * @property string|null $difficulty_level
 * @property array|null $question_types
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ListeningTask extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'listening_tasks';
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

    // Retake options (following writing pattern)
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
        'audio_url',
        'audio_duration_seconds',
        'transcript',
        'audio_segments',
        'suggest_time_minutes',
        'max_attempts_per_audio',
        'show_transcript',
        'allow_replay',
        'replay_settings',
        'difficulty_level',
        'question_types',
    ];

    protected $casts = [
        'allow_retake' => 'boolean',
        'retake_options' => 'array',
        'allow_submission_files' => 'boolean',
        'is_published' => 'boolean',
        'audio_segments' => 'array',
        'show_transcript' => 'boolean',
        'allow_replay' => 'boolean',
        'replay_settings' => 'array',
        'question_types' => 'array',
    ];

    /**
     * Get the creator of this task
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all assignments for this task
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ListeningTaskAssignment::class, 'listening_task_id');
    }

    /**
     * Get all submissions for this task
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ListeningSubmission::class, 'listening_task_id');
    }

    /**
     * Get all questions for this task
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ListeningQuestion::class, 'listening_task_id')->ordered();
    }

    /**
     * Get audio segments for this task
     */
    public function audioSegments(): HasMany
    {
        return $this->hasMany(ListeningAudioSegment::class, 'listening_task_id');
    }

    /**
     * Scope for published tasks
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for tasks by difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Check if retakes are allowed
     */
    public function allowsRetakes(): bool
    {
        return $this->allow_retake && $this->max_retake_attempts > 0;
    }

    /**
     * Get total possible points for this task
     */
    public function getTotalPoints(): int
    {
        return $this->questions->sum('points');
    }

    /**
     * Calculate score percentage
     */
    public function calculateScorePercentage(int $pointsEarned): float
    {
        $totalPoints = $this->getTotalPoints();
        return $totalPoints > 0 ? ($pointsEarned / $totalPoints) * 100 : 0;
    }
}