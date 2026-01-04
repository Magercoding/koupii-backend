<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Class\ClassRequest;
use App\Http\Resources\V1\Class\ClassResource;
use App\Models\ClassEnrollment;
use App\Models\Classes;
use App\Services\V1\Class\ClassService;
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

    public function update(ClassRequest $request, ClassService $service, $id)
    {
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
                }
            } else {
               
                ClassEnrollment::create([
                    'class_id' => $class->id,
                    'student_id' => $user->id,
                    'status' => 'active',
                    'enrolled_at' => now()
                ]);
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
