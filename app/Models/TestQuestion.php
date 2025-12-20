<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $question_group_id
 * @property string $question_type
 * @property float|null $question_number
 * @property string|null $question_text
 * @property array|null $question_data
 * @property array|null $correct_answers
 * @property float $points_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TestQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'test_questions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'question_group_id',
        'question_type',
        'question_number',
        'question_text',
        'question_data',
        'correct_answers',
        'points_value',
    ];

    protected $casts = [
        'question_number' => 'float',
        'question_data' => 'array',
        'correct_answers' => 'array',
        'points_value' => 'float',
    ];

    public function questionGroup()
    {
        return $this->belongsTo(QuestionGroup::class, 'question_group_id', 'id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function studentAttempts()
    {
        return $this->hasMany(StudentQuestionAttempt::class, 'question_id');
    }

    public function breakdowns()
    {
        return $this->hasMany(QuestionBreakdown::class, 'question_id');
    }

    public function highlightSegments()
    {
        return $this->hasMany(HighlightSegment::class, 'question_id');
    }

    public function readingAnswers()
    {
        return $this->hasMany(ReadingQuestionAnswer::class, 'question_id');
    }
}
