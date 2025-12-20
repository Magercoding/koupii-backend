<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakingTaskAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'test_id',
        'class_id',
        'assigned_by',
        'due_date',
        'assigned_at',
        'allow_retake',
        'max_attempts'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'assigned_at' => 'datetime',
        'allow_retake' => 'boolean'
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
