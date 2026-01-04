<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $teacher_id
 * @property string $name
 * @property string $description
 * @property string $class_code
 * @property string $cover_image
 * @property string $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Classes extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'classes';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'class_code',
        'cover_image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'class_enrollments',
            'class_id',
            'student_id'
        )->withPivot('status', 'enrolled_at')->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    public function invitations()
    {
        return $this->hasMany(ClassInvitation::class, 'class_id');
    }

    public function vocabularies()
    {
        return $this->belongsToMany(Vocabulary::class, 'class_vocabularies', 'class_id', 'vocabulary_id')->withPivot('assigned_at')->withTimestamps();
    }

    public function tests()
    {
        return $this->hasMany(Test::class, 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

 
    public function analytics()
    {
        return $this->hasMany(ClassAnalytic::class, 'class_id');
    }

    public function testReports()
    {
        return $this->hasMany(TestReport::class, 'class_id');
    }

    public function scopeForAdmin($query)
    {
        return $query->with(['teacher:id,name,email,avatar,bio', 'students:id,name,email,avatar']);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId)
            ->with(['teacher:id,name,email,avatar,bio', 'students:id,name,email,avatar']);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->whereHas('students', function ($q) use ($studentId) {
            $q->where('users.id', $studentId);
        })
            ->with(['teacher:id,name,email,avatar,bio', 'students:id,name,email,avatar']);
    }
    public function scopeVisibleTo($query, $user)
    {
        return match ($user->role) {
            'admin' => $query,
            'teacher' => $query->where('teacher_id', $user->id),
            'student' => $query->whereHas(
                'students',
                fn($q) =>
                $q->where('users.id', $user->id)
            ),
            default => $query->whereRaw('0 = 1'),
        };
    }


}