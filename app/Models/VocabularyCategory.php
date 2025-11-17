<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $name
 * @property string $color_code
 */
class VocabularyCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vocabulary_categories';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'color_code',
    ];

    public function vocabularies()
    {
        return $this->hasMany(Vocabulary::class, 'category_id');
    }

    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where('name', 'like', "%$search%");
        }

        return $query;
    }

    
    public function scopePerPage($query, $perPage)
    {
        return $query->paginate($perPage ?? 10);
    }
}
