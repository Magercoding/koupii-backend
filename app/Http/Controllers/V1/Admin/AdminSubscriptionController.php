<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherSubscription;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    /**
     * List all subscriptions with user and plan data.
     */
    public function index(Request $request)
    {
        $query = TeacherSubscription::with(['user:id,name,email', 'plan:id,name,price'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by user name or email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get subscription statistics for the admin overview.
     */
    public function stats()
    {
        $activeCount = TeacherSubscription::where('status', 'active')->count();
        $totalRevenue = TeacherSubscription::where('status', 'active')
            ->join('teacher_plans', 'teacher_subscriptions.teacher_plan_id', '=', 'teacher_plans.id')
            ->sum('teacher_plans.price');
        $canceledCount = TeacherSubscription::where('status', 'cancelled')->count();

        $totalEver = TeacherSubscription::count();
        $churnRate = $totalEver > 0 ? round(($canceledCount / $totalEver) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'active_subscribers' => $activeCount,
                'monthly_revenue' => number_format($totalRevenue, 2),
                'churn_rate' => $churnRate,
                'total_subscriptions' => $totalEver,
            ],
        ]);
    }
}
