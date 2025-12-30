<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $assignment_id
 * @property string $student_id
 * @property string $status
 * @property float|null $score
 * @property int $attempt_number
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
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
        'assignment_type',
        'test_id',
        'status',
        'score',
        'attempt_number',
        'attempt_count',
        'started_at',
        'completed_at',
        'assigned_at',
        'time_spent_seconds',
        'submission_data',
    ];

    protected $casts = [
        'status' => 'string',
        'score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'submission_data' => 'array',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function questionAttempts()
    {
        return $this->hasMany(StudentQuestionAttempt::class, 'student_assignment_id');
    }

    public function testResult()
    {
        return $this->hasOne(TestResult::class, 'student_assignment_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
}
