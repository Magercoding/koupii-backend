<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Writing Attempt Model for tracking student retake attempts
 * 
 * @property string $id
 * @property string $writing_task_id
 * @property string $student_id
 * @property int $attempt_number
 * @property string $attempt_type
 * @property array|null $selected_questions
 * @property string $status
 * @property int|null $time_taken_seconds
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_attempts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'writing_task_id',
        'student_id',
        'attempt_number',
        'attempt_type',
        'selected_questions',
        'status',
        'time_taken_seconds',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'selected_questions' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Attempt types from your Figma design
    public const ATTEMPT_TYPES = [
        'whole_essay' => 'Rewrite Whole Essay',
        'choose_questions' => 'Choose Any Multiple Questions',
        'specific_questions' => 'Specific Questions Only',
        'first_attempt' => 'First Attempt',
    ];

    // Attempt statuses
    public const STATUSES = [
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'completed' => 'Completed',
        'abandoned' => 'Abandoned',
    ];

    /**
     * Get the writing task
     */
    public function writingTask()
    {
        return $this->belongsTo(WritingTask::class, 'writing_task_id');
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get all submissions for this attempt
     */
    public function submissions()
    {
        return $this->hasMany(WritingSubmission::class, 'attempt_id');
    }

    /**
     * Get feedback for this attempt
     */
    public function feedback()
    {
        return $this->hasManyThrough(
            WritingFeedback::class,
            WritingSubmission::class,
            'attempt_id',
            'submission_id'
        );
    }

    /**
     * Check if this is a retake attempt
     */
    public function getIsRetakeAttribute()
    {
        return $this->attempt_number > 1;
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationAttribute()
    {
        if (!$this->time_taken_seconds) return null;

        $hours = floor($this->time_taken_seconds / 3600);
        $minutes = floor(($this->time_taken_seconds % 3600) / 60);
        $seconds = $this->time_taken_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Calculate overall score for this attempt
     */
    public function getOverallScoreAttribute()
    {
        $submissions = $this->submissions()->with('feedback')->get();
        
        if ($submissions->isEmpty()) return null;

        $totalScore = 0;
        $totalMaxScore = 0;

        foreach ($submissions as $submission) {
            $feedback = $submission->feedback()->where('feedback_type', 'overall')->first();
            if ($feedback) {
                $totalScore += $feedback->score ?? 0;
                $totalMaxScore += $feedback->max_score ?? 0;
            }
        }

        if ($totalMaxScore == 0) return null;

        return round(($totalScore / $totalMaxScore) * 100, 2);
    }
}