<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\User\ChangePasswordRequest;
use App\Services\V1\User\PasswordService;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function changePassword(ChangePasswordRequest $request, PasswordService $service)
    {

        $user = $service->changePassword($request->validated());

     
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Password changed successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

}
