<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Test;
use App\Models\User;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function getOverview(): JsonResponse
    {
        $roleCounts = collect(User::select('role', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get());

        $overview = [
            'total_users' => User::count(),
            'total_students' => $roleCounts->firstWhere('role', 'student')->count ?? 0,
            'total_teachers' => $roleCounts->firstWhere('role', 'teacher')->count ?? 0,
            'total_admins' => $roleCounts->firstWhere('role', 'admin')->count ?? 0,
            'total_classes' => Classes::count(),
            'total_enrollments' => ClassEnrollment::count(),
            'total_tests' => Test::count() + 
                             \App\Models\ListeningTask::count() + 
                             \App\Models\SpeakingTask::count() + 
                             \App\Models\WritingTask::count(),
            'total_assignments' => Assignment::count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $overview
        ]);
    }
}
