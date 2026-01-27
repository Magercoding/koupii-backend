<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $student_assignment_id
 * @property string|null $old_status
 * @property string $new_status
 * @property array|null $metadata
 * @property string|null $changed_by
 * @property \Carbon\Carbon $changed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AssignmentAuditTrail extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'assignment_audit_trail';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'student_assignment_id',
        'old_status',
        'new_status',
        'metadata',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    // Relationships
    public function studentAssignment()
    {
        return $this->belongsTo(StudentAssignment::class, 'student_assignment_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Scopes
    public function scopeForAssignment($query, string $assignmentId)
    {
        return $query->where('student_assignment_id', $assignmentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('new_status', $status);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }
}