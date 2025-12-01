<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $class_id
 * @property string $teacher_id
 * @property string $student_id
 * @property string $email
 * @property string $invitation_token
 * @property string $status
 * @property string $expires_at
 */
class ClassInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'class_invitations';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'student_id',
        'email',
        'invitation_token',
        'status',
        'expires_at',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }


    public function scopeVisibleTo($query, $user)
    {
        return match ($user->role) {
            'admin' => $query->with(['class', 'student', 'teacher']),

            'teacher' => $query
                ->whereHas('class', fn($q) => $q->where('teacher_id', $user->id))
                ->with(['class', 'student']),

            'student' => $query
                ->where('student_id', $user->id)
                ->with([
                    'class',
                    'student',
                    'teacher' => fn($q) => $q->select('id', 'name')
                ]),

            default => $query->whereRaw('0 = 1'),
        };
    }

}
