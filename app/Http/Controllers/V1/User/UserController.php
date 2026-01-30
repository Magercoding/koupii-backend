<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\V1\User\UserService;
class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return new UserResource($user);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return new UserResource($user);
    }

    public function update(UpdateProfileRequest $request, UserService $userService)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        try {
            // Get validated data
            $validatedData = $request->validated();
            
            // Handle file upload if present
            if ($request->hasFile('avatar')) {
                $validatedData['avatar_file'] = $request->file('avatar');
            }

            $result = $userService->updateProfile($user, $validatedData, $request);

            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], $result['code']);
            }

            return response()->json([
                'message' => 'User updated successfully',
                'user' => new UserResource($result['user'])
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, UserService $userService)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $userService->deleteProfile($user);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code'] ?? 500);
        }

        return response()->json([
            'message' => 'User deleted successfully',
            'user' => new UserResource($result['user'])
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => ['current_password' => ['Current password is incorrect']]
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }
}
