<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\TeacherPlan;

class PublicPlanController extends Controller
{
    /**
     * List all active plans for the public pricing page.
     */
    public function index()
    {
        $plans = TeacherPlan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get(['id', 'name', 'description', 'price', 'benefits']);

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }
}
