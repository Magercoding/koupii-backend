<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $submission_id
 * @property string|null $teacher_id
 * @property int|null $score
 * @property string|null $comments
 * @property array|null $feedback_json
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingReview extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_reviews';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'submission_id',
        'teacher_id',
        'score',
        'comments',
        'feedback_json',
        'reviewed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'feedback_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(WritingSubmission::class, 'submission_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}