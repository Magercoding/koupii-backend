<?php

namespace App\Http\Controllers\V1\Class;

use App\Http\Controllers\Controller;
use App\Services\V1\Class\ClassAnalyticsService;
use Illuminate\Http\Request;
use Exception;

class ClassAnalyticsController extends Controller
{
    protected $service;

    public function __construct(ClassAnalyticsService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request, $classId)
    {
        try {
            $type  = $request->query('type', 'reading');
            $month = $request->query('month');

            $analytics = $this->service->getClassAnalytics($classId, $type, $month);

            return response()->json([
                'status' => 'success',
                'data'   => $analytics
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch class analytics: ' . $e->getMessage()
            ], 500);
        }
    }
}
