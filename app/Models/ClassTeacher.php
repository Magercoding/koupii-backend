<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassTeacher extends Pivot
{
    use HasUuids;

    protected $table = 'class_teachers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
