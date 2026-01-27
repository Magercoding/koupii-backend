<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

/**
 * @property string $id
 * @property string $assignment_id
 * @property string $student_id
 * @property string|null $test_id
 * @property string|null $assignment_type
 * @property string $status
 * @property float|null $score
 * @property int $attempt_number
 * @property int $attempt_count
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $assigned_at
 * @property int|null $time_spent_seconds
 * @property int $time_spent_minutes
 * @property array|null $submission_data
 * @property array|null $progress_data
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StudentAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_assignments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'test_id',
        'assignment_type',
        'status',
        'score',
        'attempt_number',
        'attempt_count',
        'started_at',
        'completed_at',
        'assigned_at',
        'time_spent_seconds',
        'time_spent_minutes',
        'submission_data',
        'progress_data',
        'last_activity_at',
    ];

    protected $casts = [
        'status' => 'string',
        'score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'submission_data' => 'array',
        'progress_data' => 'array',
    ];

    // Status constants
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_GRADED = 'graded';

    // Relationships
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function questionAttempts()
    {
        return $this->hasMany(StudentQuestionAttempt::class, 'student_assignment_id');
    }

    public function testResult()
    {
        return $this->hasOne(TestResult::class, 'student_assignment_id');
    }

    public function auditTrail()
    {
        return $this->hasMany(AssignmentAuditTrail::class, 'student_assignment_id');
    }

    // Enhanced methods for assignment status tracking
    public function start(): bool
    {
        if ($this->status !== self::STATUS_NOT_STARTED) {
            return false;
        }

        $oldStatus = $this->status;
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'last_activity_at' => now()
        ]);

        $this->recordStatusChange($oldStatus, self::STATUS_IN_PROGRESS);
        return true;
    }

    public function complete(): bool
    {
        if (!in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_NOT_STARTED])) {
            return false;
        }

        $oldStatus = $this->status;
        $completedAt = now();
        
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt,
            'last_activity_at' => $completedAt,
            'time_spent_minutes' => $this->calculateTimeSpent($completedAt)
        ]);

        $this->recordStatusChange($oldStatus, self::STATUS_COMPLETED);
        return true;
    }

    public function submit(): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        $oldStatus = $this->status;
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'last_activity_at' => now()
        ]);

        $this->recordStatusChange($oldStatus, self::STATUS_SUBMITTED);
        return true;
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function calculateTimeSpent(?Carbon $endTime = null): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $endTime ?: now();
        return $this->started_at->diffInMinutes($endTime);
    }

    public function isOverdue(): bool
    {
        if (!$this->assignment || !$this->assignment->due_date) {
            return false;
        }

        return now()->isAfter($this->assignment->due_date) && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', self::STATUS_NOT_STARTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeOverdue($query)
    {
        return $query->whereHas('assignment', function($q) {
            $q->where('due_date', '<', now());
        })->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForAssignment($query, string $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    // Private helper methods
    private function recordStatusChange(string $oldStatus, string $newStatus): void
    {
        AssignmentAuditTrail::create([
            'student_assignment_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]
        ]);
    }
}
