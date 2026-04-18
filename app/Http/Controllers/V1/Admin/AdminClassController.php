<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminClassController extends Controller
{
    /**
     * Get all classes with teacher and student counts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Classes::with(['teacher:id,name,email'])
            ->withCount('students');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacher', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $classes
        ]);
    }

    /**
     * Delete a class (Admin only)
     */
    public function destroy(string $id): JsonResponse
    {
        $class = Classes::findOrFail($id);
        $class->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Class deleted successfully.'
        ]);
    }
}
