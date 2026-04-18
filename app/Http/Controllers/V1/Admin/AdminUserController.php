<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Get paginated list of users (can filter by search or role)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
        ]);

        $user = User::findOrFail($id);
        
        // Prevent admin from removing their own admin role accidentally
        if ($user->id === $request->user()->id && $request->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot remove your own admin role.'
            ], 403);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully.',
            'data' => $user
        ]);
    }
}
