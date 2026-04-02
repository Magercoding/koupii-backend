<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * @property string $id
 * @property string $class_id
 * @property string|null $test_id
 * @property string|null $task_id
 * @property string|null $task_type
 * @property string|null $assigned_by
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $close_date
 * @property bool $is_published
 * @property int $max_attempts
 * @property string|null $instructions
 * @property string $status
 * @property string $source_type
 * @property string|null $source_id
 * @property array|null $assignment_settings
 * @property \Carbon\Carbon|null $auto_created_at
 * @property string|null $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Assignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'class_id',
        'test_id',
        'task_id',
        'task_type',
        'assigned_by',
        'title',
        'description',
        'due_date',
        'close_date',
        'is_published',
        'max_attempts',
        'instructions',
        'status',
        'source_type',
        'source_id',
        'assignment_settings',
        'auto_created_at',
        'type',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'due_date' => 'datetime',
        'close_date' => 'datetime',
        'auto_created_at' => 'datetime',
        'assignment_settings' => 'array',
    ];

    // Relationships
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentAssignment::class, 'assignment_id');
    }

    public function sourceTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'source_id')
            ->where('source_type', 'auto_test');
    }

    /**
     * Get the related task model based on task_type.
     */
    public function getTask()
    {
        if (!$this->task_id || !$this->task_type) {
            return null;
        }

        $model = match ($this->task_type) {
            'writing_task' => WritingTask::class,
            'reading_task' => ReadingTask::class,
            'listening_task' => ListeningTask::class,
            'speaking_task' => SpeakingTask::class,
            default => null
        };

        return $model ? $model::find($this->task_id) : null;
    }

    /**
     * Check if this assignment is test-based.
     */
    public function isTestBased(): bool
    {
        return $this->test_id !== null;
    }

    /**
     * Check if this assignment is task-based.
     */
    public function isTaskBased(): bool
    {
        return $this->task_id !== null && $this->task_type !== null;
    }

    public function isAutoCreated(): bool
    {
        return $this->source_type === 'auto_test';
    }

    public function canBeAutoAssigned(): bool
    {
        return $this->is_published && $this->class_id !== null;
    }

    public function getAssignmentTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->isTestBased() && $this->test) {
            return $this->test->title;
        }

        $task = $this->getTask();
        return $task?->title ?? 'Untitled Assignment';
    }

    public function getDefaultDueDate(): Carbon
    {
        return now()->addDays(7);
    }

    // Scopes
    public function scopeAutoCreated($query)
    {
        return $query->where('source_type', 'auto_test');
    }

    public function scopeManuallyCreated($query)
    {
        return $query->where('source_type', 'manual');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForClass($query, string $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
