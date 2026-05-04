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

    public function getReviewQueue(): JsonResponse
    {
        $writingSubmissions = \App\Models\WritingSubmission::with(['student', 'writingTask'])
            ->whereNull('assignment_id')
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get()
            // Only keep the latest attempt per student per task to avoid spam
            ->unique(function ($item) {
                return $item->student_id . $item->writing_task_id;
            })
            ->values()
            ->map(function ($s) {
                $s->type = 'writing';
                $s->test_title = $s->writingTask?->title ?? 'Writing Test';
                return $s;
            });

        $speakingSubmissions = \App\Models\SpeakingSubmission::with(['student', 'speakingTask'])
            ->whereNull('assignment_id')
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get()
            // Only keep the latest attempt per student per task to avoid spam
            ->unique(function ($item) {
                return $item->student_id . $item->speaking_task_id;
            })
            ->values()
            ->map(function ($s) {
                $s->type = 'speaking';
                $s->test_title = $s->speakingTask?->title ?? 'Speaking Test';
                return $s;
            });

        $queue = $writingSubmissions->concat($speakingSubmissions)
            ->sortByDesc('submitted_at')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $queue
        ]);
    }
}
