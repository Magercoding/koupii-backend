<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Class\ClassEnrollmentRequest;
use App\Http\Resources\V1\Class\ClassEnrollmentCollection;
use App\Http\Resources\V1\Class\ClassEnrollmentResource;
use App\Models\ClassEnrollment;
use App\Events\StudentEnrolledInClass;
use Illuminate\Support\Facades\Gate;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassEnrollmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $enrollments = match ($user->role) {
            'admin' => ClassEnrollment::forAdmin()->paginate(10),
            'teacher' => ClassEnrollment::forTeacher($user->id),
            'student' => ClassEnrollment::forStudent($user->id),
            default => abort(403, 'Unauthorized'),
        };

        if (!$enrollments) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $paginated = $enrollments->paginate(10);
        return ClassEnrollmentCollection::collection($paginated);
    }

    public function store(ClassEnrollmentRequest $request)
    {
        $data = $request->validated();

        $enrollment = ClassEnrollment::create($data);

        // Dispatch event for automatic assignment creation
        if ($enrollment->status === 'active') {
            StudentEnrolledInClass::dispatch($enrollment->student, $enrollment->class);
        }

        return response()->json([
            'message' => 'Enrollment created successfully',
            'data' => new ClassEnrollmentResource($enrollment),
        ], 200);
    }

    public function show(ClassEnrollment $id)
    {

        if (!$id) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        return new ClassEnrollmentResource($id);
    }

    public function update(ClassEnrollmentRequest $request, ClassEnrollment $id)
    {
        Gate::authorize('update', $id);

        $originalStatus = $id->status;
        $validatedData = $request->validated();
        
        $id->update($validatedData);

        // Dispatch event if enrollment status changed to active
        if ($originalStatus !== 'active' && $validatedData['status'] === 'active') {
            StudentEnrolledInClass::dispatch($id->student, $id->class);
        }

        return response()->json([
            'message' => 'Enrollment updated successfully',
            'data' => new ClassEnrollmentResource($id),
        ]);
    }
    public function destroy(ClassEnrollment $id)
    {
        Gate::authorize('delete', $id);
        $id->delete();
        return response()->json([
            'message' => 'Enrollment deleted successfully',
        ]);
    }
}
