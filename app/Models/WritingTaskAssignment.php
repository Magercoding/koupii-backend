<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $writing_task_id
 * @property string|null $classroom_id
 * @property string|null $assigned_by
 * @property \Carbon\Carbon|null $assigned_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingTaskAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_task_assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'writing_task_id',
        'classroom_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function writingTask()
    {
        return $this->belongsTo(WritingTask::class, 'writing_task_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}