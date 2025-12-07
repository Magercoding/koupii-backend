<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Services\V1\WritingTask\WritingDashboardService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class WritingDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }
    /**
     * Get student dashboard.
     */
    public function student(Request $request)
    {
        if (Auth::user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new WritingDashboardService();
            $dashboard = $service->getStudentDashboard();

            return response()->json([
                'message' => 'Student dashboard retrieved successfully',
                'data' => $dashboard,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teacher dashboard.
     */
    public function teacher(Request $request)
    {
        if (!in_array(Auth::user()->role, ['teacher', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new WritingDashboardService();
            $dashboard = $service->getTeacherDashboard();

            return response()->json([
                'message' => 'Teacher dashboard retrieved successfully',
                'data' => $dashboard,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get admin dashboard (overview of all tasks).
     */
    public function admin(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $service = new WritingDashboardService();
            $dashboard = $service->getAdminDashboard();

            return response()->json([
                'message' => 'Admin dashboard retrieved successfully',
                'data' => $dashboard,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}