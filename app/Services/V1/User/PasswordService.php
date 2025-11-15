<?php

namespace App\Services\V1\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordService
{
    public function changePassword(array $data, bool $revokeTokens = true): User
    {
        $user = auth()->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw new \Exception('Current password is incorrect', 401);
        }

        return DB::transaction(function () use ($user, $data, $revokeTokens) {

        
            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);

        
            if ($revokeTokens) {
                $user->tokens()->delete();
            }

            return $user;
        });
    }
}
