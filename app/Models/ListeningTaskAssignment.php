<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $listening_task_id
 * @property string|null $classroom_id
 * @property string|null $assigned_by
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $assigned_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ListeningTaskAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'listening_task_assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'listening_task_id',
        'classroom_id',
        'assigned_by',
        'due_date',
        'assigned_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the listening task for this assignment
     */
    public function listeningTask(): BelongsTo
    {
        return $this->belongsTo(ListeningTask::class, 'listening_task_id');
    }

    /**
     * Get the teacher who assigned this task
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the classroom this task was assigned to
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'classroom_id');
    }
}