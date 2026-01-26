<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $reading_task_id
 * @property string|null $classroom_id
 * @property string|null $assigned_by
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $assigned_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReadingTaskAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reading_task_assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reading_task_id',
        'class_id',
        'classroom_id', // Add this back for legacy support/constraint safety
        'assigned_by',
        'due_date',
        'assigned_at',
        'status',
        'max_attempts',
        'instructions',
        'auto_grade',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the reading task for this assignment
     */
    public function readingTask(): BelongsTo
    {
        return $this->belongsTo(ReadingTask::class, 'reading_task_id');
    }

    /**
     * Get the teacher who assigned this task
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the class this task was assigned to
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Legacy accessor for backward compatibility if needed, using new column
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}