<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $writing_question_id
 * @property string $resource_type
 * @property string $file_path
 * @property string $file_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $description
 * @property int $display_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WritingTaskQuestionResource extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'writing_task_question_resources';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'writing_question_id',
        'resource_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
        'display_order',
    ];

    // Resource types constants
    public const RESOURCE_TYPES = [
        'image' => 'Image',
        'audio' => 'Audio',
        'video' => 'Video',
        'document' => 'Document',
    ];

    /**
     * Relationships
     */
    public function writingQuestion()
    {
        return $this->belongsTo(WritingTaskQuestion::class, 'writing_question_id');
    }

    /**
     * Helpers
     */
    public function getResourceTypeNameAttribute()
    {
        return self::RESOURCE_TYPES[$this->resource_type] ?? $this->resource_type;
    }

    public function getFileUrlAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    public function isImage()
    {
        return $this->resource_type === 'image';
    }

    public function isAudio()
    {
        return $this->resource_type === 'audio';
    }

    public function isVideo()
    {
        return $this->resource_type === 'video';
    }

    public function isDocument()
    {
        return $this->resource_type === 'document';
    }
}