<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SpeakingTask extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'description',
        'instructions',
        'difficulty_level',
        'time_limit_seconds',
        'topic',
        'situation_context',
        'questions',
        'sample_audio',
        'rubric',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'questions' => 'array',
        'rubric' => 'array',
        'is_published' => 'boolean',
        'time_limit_seconds' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the speaking task assignments for this task
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SpeakingTaskAssignment::class);
    }

    /**
     * Get the speaking submissions for this task
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(SpeakingSubmission::class);
    }

    /**
     * Get the creator of this task
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for published tasks
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for tasks by difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }
}