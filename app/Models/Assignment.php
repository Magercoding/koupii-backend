<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

/**
 * @property string $id
 * @property string $class_id
 * @property string|null $test_id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $close_date
 * @property bool $is_published
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
        'title',
        'description',
        'due_date',
        'close_date',
        'is_published',
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
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function studentAssignments()
    {
        return $this->hasMany(StudentAssignment::class, 'assignment_id');
    }

    public function sourceTest()
    {
        return $this->belongsTo(Test::class, 'source_id')
            ->where('source_type', 'auto_test');
    }

    // Enhanced methods for automatic assignment system
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
        return $this->title ?: ($this->test ? $this->test->title : 'Untitled Assignment');
    }

    public function getDefaultDueDate(): Carbon
    {
        return now()->addDays(7); // Default 7 days from creation
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
}
