<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $test_id
 * @property string $task_type
 * @property string|null $title
 * @property string|null $description
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

    protected $fillable = [
        'test_id',
        'task_type',
        'title',
        'description',
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
        'question_types'
    ];

    protected $casts = [
        'audio_segments' => 'array',
        'show_transcript' => 'boolean',
        'allow_replay' => 'boolean',
        'replay_settings' => 'array',
        'question_types' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Question type constants for the 15 types
    public const QUESTION_TYPES = [
        'QT1' => 'multiple_choice',
        'QT2' => 'multiple_answer',
        'QT3' => 'matching_labeling',
        'QT4' => 'table_completion',
        'QT5' => 'sentence_completion',
        'QT6' => 'short_answer',
        'QT7' => 'form_completion',
        'QT8' => 'note_completion',
        'QT9' => 'flowchart_completion',
        'QT10' => 'summary_completion',
        'QT11' => 'diagram_labeling',
        'QT12' => 'classification',
        'QT13' => 'true_false_not_given',
        'QT14' => 'gap_fill_listening',
        'QT15' => 'audio_dictation'
    ];

    // Task type constants
    public const TASK_TYPES = [
        'conversation' => 'Conversation',
        'monologue' => 'Monologue',
        'lecture' => 'Academic Lecture',
        'discussion' => 'Discussion',
        'interview' => 'Interview',
        'news' => 'News Report',
        'announcement' => 'Announcement',
        'story' => 'Story/Narrative'
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

    /**
     * Get the test that owns this listening task
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get all questions for this listening task
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TestQuestion::class, 'listening_task_id');
    }

    /**
     * Get audio segments for this task
     */
    public function audioSegments(): HasMany
    {
        return $this->hasMany(ListeningAudioSegment::class, 'listening_task_id');
    }

    /**
     * Get submissions for this listening task
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ListeningSubmission::class, 'test_id', 'test_id');
    }

    /**
     * Check if task allows replay
     */
    public function allowsReplay(): bool
    {
        return $this->allow_replay;
    }

    /**
     * Get maximum replay attempts
     */
    public function getMaxReplayAttempts(): int
    {
        return $this->replay_settings['max_attempts'] ?? 3;
    }

    /**
     * Check if transcript should be shown
     */
    public function shouldShowTranscript(): bool
    {
        return $this->show_transcript;
    }

    /**
     * Get formatted audio duration
     */
    public function getFormattedDuration(): string
    {
        if (!$this->audio_duration_seconds) {
            return '00:00';
        }

        $minutes = floor($this->audio_duration_seconds / 60);
        $seconds = $this->audio_duration_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get question type labels
     */
    public function getQuestionTypeLabels(): array
    {
        $labels = [];
        foreach ($this->question_types ?? [] as $type) {
            $labels[] = self::QUESTION_TYPES[$type] ?? $type;
        }
        return $labels;
    }

    /**
     * Check if task has specific question type
     */
    public function hasQuestionType(string $type): bool
    {
        return in_array($type, $this->question_types ?? []);
    }

    /**
     * Get task statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_questions' => $this->questions()->count(),
            'total_submissions' => $this->submissions()->count(),
            'completed_submissions' => $this->submissions()->where('status', 'completed')->count(),
            'average_score' => $this->submissions()
                ->where('status', 'completed')
                ->avg('percentage') ?? 0,
            'audio_duration' => $this->getFormattedDuration(),
            'question_types_count' => count($this->question_types ?? [])
        ];
    }

    /**
     * Scope for specific task type
     */
    public function scopeByTaskType($query, string $taskType)
    {
        return $query->where('task_type', $taskType);
    }

    /**
     * Scope for specific difficulty level
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope for tasks with replay allowed
     */
    public function scopeWithReplay($query)
    {
        return $query->where('allow_replay', true);
    }
}