<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UpdateProfileRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Handle method override for file uploads (POST with _method=PATCH)
        $validatedData = $request->validated();
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
}
