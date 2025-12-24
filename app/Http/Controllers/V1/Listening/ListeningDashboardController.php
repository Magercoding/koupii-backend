<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ListeningDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get student dashboard data.
     */
    public function student(Request $request)
    {
        return response()->json([
            'message' => 'Student listening dashboard',
            'data' => []
        ]);
    }

    /**
     * Get teacher dashboard data.
     */
    public function teacher(Request $request)
    {
        return response()->json([
            'message' => 'Teacher listening dashboard',
            'data' => []
        ]);
    }

    /**
     * Get admin dashboard data.
     */
    public function admin(Request $request)
    {
        return response()->json([
            'message' => 'Admin listening dashboard',
            'data' => []
        ]);
    }
}