<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Class\ClassRequest;
use App\Http\Resources\V1\Class\ClassResource;
use App\Http\Resources\V1\User\UserResource;
use App\Models\ClassEnrollment;
use App\Models\Classes;
use App\Services\V1\Class\ClassService;
use App\Events\StudentEnrolledInClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
     * Join class by code (for students)
     */
    public function joinByCode(Request $request): JsonResponse
    {
        $request->validate([
            'class_code' => 'required|string|exists:classes,class_code'
        ]);

        try {
            $user = Auth::user();

            if ($user->role !== 'student') {
                return response()->json([
                    'message' => 'Only students can join classes'
                ], 403);
            }

            $class = Classes::where('class_code', $request->class_code)
                ->where('is_active', true)
                ->firstOrFail();

          
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
                    
                    // Dispatch event for reactivated enrollment
                    StudentEnrolledInClass::dispatch($user, $class);
                }
            } else {
               
                $enrollment = ClassEnrollment::create([
                    'class_id' => $class->id,
                    'student_id' => $user->id,
                    'status' => 'active',
                    'enrolled_at' => now()
                ]);
                
                // Dispatch event for new enrollment
                StudentEnrolledInClass::dispatch($user, $class);
            }

            return response()->json([
                'message' => 'Successfully joined class: ' . $class->name,
                'class' => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'description' => $class->description,
                    'teacher' => $class->teacher->name,
                    'student_count' => $class->enrollments()->where('status', 'active')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to join class',
                'error' => $e->getMessage()
            ], 422);
        }
    }

}
