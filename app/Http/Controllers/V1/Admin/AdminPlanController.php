<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPlanController extends Controller
{
    /**
     * List all plans (admin view).
     */
    public function index()
    {
        $plans = TeacherPlan::withCount('subscriptions')
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }

    /**
     * Create a new plan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'benefits' => 'required|array|min:1',
            'benefits.*' => 'string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = TeacherPlan::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Plan created successfully',
            'data' => $plan,
        ], 201);
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, string $id)
    {
        $plan = TeacherPlan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'benefits' => 'sometimes|required|array|min:1',
            'benefits.*' => 'string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Plan updated successfully',
            'data' => $plan->fresh(),
        ]);
    }

    /**
     * Delete a plan.
     */
    public function destroy(string $id)
    {
        $plan = TeacherPlan::findOrFail($id);

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete plan with active subscribers',
            ], 409);
        }

        $plan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Plan deleted successfully',
        ]);
    }
}
