<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $writing_task_id
 * @property string $question_type
 * @property int $question_number
 * @property string $question_text
 * @property string|null $instructions
 * @property int|null $word_limit
 * @property int|null $min_word_count
 * @property float $points
 * @property string|null $rubric
 * @property string|null $sample_answer
 * @property array|null $question_data
 * @property bool $is_required
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingTaskQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_task_questions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'writing_task_id',
        'question_type',
        'question_number',
        'question_text',
        'instructions',
        'word_limit',
        'min_word_count',
        'points',
        'rubric',
        'sample_answer',
        'question_data',
        'is_required',
    ];

    protected $casts = [
        'question_data' => 'array',
        'is_required' => 'boolean',
        'points' => 'decimal:2',
    ];

    // Question types constants
    public const QUESTION_TYPES = [
        'essay' => 'Essay',
        'short_answer' => 'Short Answer',
        'creative_writing' => 'Creative Writing',
        'argumentative' => 'Argumentative Writing',
        'descriptive' => 'Descriptive Writing',
        'narrative' => 'Narrative Writing',
        'summary' => 'Summary Writing',
        'letter' => 'Letter Writing',
        'report' => 'Report Writing',
    ];

    /**
     * Relationships
     */
    public function writingTask()
    {
        return $this->belongsTo(WritingTask::class, 'writing_task_id');
    }

    public function resources()
    {
        return $this->hasMany(WritingTaskQuestionResource::class, 'writing_question_id');
    }

    /**
     * Helpers
     */
    public function getQuestionTypeNameAttribute()
    {
        return self::QUESTION_TYPES[$this->question_type] ?? $this->question_type;
    }

    public function hasWordLimit()
    {
        return $this->word_limit > 0;
    }

    public function hasMinWordCount()
    {
        return $this->min_word_count > 0;
    }
}