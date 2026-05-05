<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Class\ClassRequest;
use App\Http\Resources\V1\Class\ClassResource;
use App\Http\Resources\V1\User\UserResource;
use App\Models\ClassEnrollment;
use App\Models\Classes;
use App\Models\User;
use App\Services\V1\Class\ClassService;
use App\Events\StudentEnrolledInClass;
use App\Mail\CoTeacherInvitationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $classes = match ($user->role) {
            'admin' => Classes::forAdmin(),
            'teacher' => Classes::forTeacher($user->id),
            'student' => Classes::forStudent($user->id),
            default => abort(403, 'Unauthorized'),
        };

        
        $paginated = $classes->paginate(15);

    
        return ClassResource::collection($paginated)
            ->additional([
                'message' => 'Classes retrieved successfully.'
            ]);
    }


    public function store(ClassRequest $request, ClassService $service)
    {
        $result = $service->create($request->validated());

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Class created successfully',
            'data' => new ClassResource($result)
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $class = Classes::with(['teacher', 'students'])
            ->visibleTo($user)
            ->find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        return new ClassResource($class);
    }

    public function students(Request $request, $id)
    {
        $user = Auth::user();

        $class = Classes::visibleTo($user)->find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        $query = $class->students();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($request->input('per_page', 15));

        // Use UserResource so avatar URLs match /profile (full url()), not raw storage paths
        return UserResource::collection($paginated);
    }

    public function update(ClassRequest $request, ClassService $service, $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        Gate::authorize('update', $class);

        $result = $service->update($id, $request->validated());

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => 'Class updated successfully',
            'data' => new ClassResource($result),
        ], 200);
    }



    public function destroy($id)
    {
        $user = Auth::user();

        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        Gate::authorize('delete', $class);

        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }

    /**
     * Remove a student from a class by student user ID
     */
    public function removeStudent(Request $request, string $classId, string $studentId): JsonResponse
    {
        $user = Auth::user();

        $class = Classes::find($classId);
        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Only the class teacher or admin can remove students
        if ($user->role !== 'admin' && $class->teacher_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $enrollment = ClassEnrollment::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Student is not enrolled in this class'], 404);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Student removed from class successfully']);
    }
    /**
     * Join class by code (students enroll; teachers become co-teachers)
     */
    public function joinByCode(Request $request): JsonResponse
    {
        $request->validate([
            'class_code' => 'required|string|exists:classes,class_code'
        ]);

        try {
            $user = Auth::user();

            if (!in_array($user->role, ['student', 'teacher'])) {
                return response()->json([
                    'message' => 'Only students and teachers can join classes via code'
                ], 403);
            }

            $class = Classes::where('class_code', $request->class_code)
                ->where('is_active', true)
                ->firstOrFail();

            // ── Teacher joining as co-teacher ──────────────────────────────
            if ($user->role === 'teacher') {
                if ($class->teacher_id === $user->id) {
                    return response()->json([
                        'message' => 'You are already the owner of this class',
                        'class' => ['id' => $class->id, 'name' => $class->name]
                    ]);
                }

                $alreadyCoTeacher = $class->coTeachers()->whereKey($user->id)->exists();
                if ($alreadyCoTeacher) {
                    return response()->json([
                        'message' => 'You are already a co-teacher of this class',
                        'class' => ['id' => $class->id, 'name' => $class->name]
                    ]);
                }

                $class->coTeachers()->attach($user->id, ['joined_at' => now()]);

                return response()->json([
                    'message' => 'Successfully joined class as co-teacher: ' . $class->name,
                    'class' => [
                        'id'          => $class->id,
                        'name'        => $class->name,
                        'description' => $class->description,
                        'owner'       => $class->teacher->name,
                    ]
                ]);
            }

            // ── Student enrolling ──────────────────────────────────────────
            $existingEnrollment = ClassEnrollment::where('class_id', $class->id)
                ->where('student_id', $user->id)
                ->first();

            if ($existingEnrollment) {
                if ($existingEnrollment->status === 'active') {
                    return response()->json([
                        'message' => 'You are already enrolled in this class',
                        'class' => [
                            'id' => $class->id,
                            'name' => $class->name,
                            'teacher' => $class->teacher->name
                        ]
                    ]);
                } else {
                    $existingEnrollment->update([
                        'status' => 'active',
                        'enrolled_at' => now()
                    ]);
                    StudentEnrolledInClass::dispatch($user, $class);
                }
            } else {
                ClassEnrollment::create([
                    'class_id'    => $class->id,
                    'student_id'  => $user->id,
                    'status'      => 'active',
                    'enrolled_at' => now()
                ]);
                StudentEnrolledInClass::dispatch($user, $class);
            }

            return response()->json([
                'message' => 'Successfully joined class: ' . $class->name,
                'class' => [
                    'id'            => $class->id,
                    'name'          => $class->name,
                    'description'   => $class->description,
                    'teacher'       => $class->teacher->name,
                    'student_count' => $class->enrollments()->where('status', 'active')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to join class',
                'error'   => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove a co-teacher from a class (owner or admin only)
     */
    public function removeCoTeacher(Request $request, string $classId, string $teacherId): JsonResponse
    {
        $user = Auth::user();

        $class = Classes::find($classId);
        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Only the class owner or admin can remove co-teachers
        if ($user->role !== 'admin' && $class->teacher_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot remove the owner via this endpoint
        if ($class->teacher_id === $teacherId) {
            return response()->json(['message' => 'Cannot remove the class owner'], 422);
        }

        $detached = $class->coTeachers()->detach($teacherId);

        if (!$detached) {
            return response()->json(['message' => 'Teacher is not a co-teacher of this class'], 404);
        }

        return response()->json(['message' => 'Co-teacher removed successfully']);
    }

    /**
     * Invite a teacher to join as co-teacher by sending them the class code via email.
     */
    public function inviteTeacher(Request $request, string $classId): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = Auth::user();

        $class = Classes::find($classId);
        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Only the class owner or admin can invite co-teachers
        if ($user->role !== 'admin' && $class->teacher_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $email = $request->input('email');

        // Prevent inviting yourself
        if ($user->email === $email) {
            return response()->json(['message' => 'You cannot invite yourself'], 422);
        }

        // Check if the email belongs to an existing teacher who is already a co-teacher
        $invitee = User::where('email', $email)->first();
        if ($invitee) {
            if ($invitee->id === $class->teacher_id) {
                return response()->json(['message' => 'This teacher is already the class owner'], 422);
            }
            if ($class->coTeachers()->whereKey($invitee->id)->exists()) {
                return response()->json(['message' => 'This teacher is already a co-teacher of this class'], 422);
            }
        }

        try {
            Mail::to($email)->send(new CoTeacherInvitationMail($class, $user, $email));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send co-teacher invitation email', [
                'class_id' => $classId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if mail fails — invitation info is still returned
        }

        return response()->json([
            'message' => 'Invitation email sent to ' . $email,
        ]);
    }

}
