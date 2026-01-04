<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class OauthController extends Controller
{
    /**
     * @unauthenticated
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }


    /**
     * @unauthenticated
     */
    public function handleGoogleCallback(Request $request)
    {
        $socialUser = Socialite::driver('google')->stateless()->user();
        $provider = 'google';

        $social = DB::table('social_accounts')
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($social) {
            $user = User::where('id', $social->user_id)->first();
        } else {
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? '',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            DB::table('social_accounts')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Auth::login($user);

        $plainToken = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'ok',
            'user' => $user,
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * @unauthenticated
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }
    /**
     * @unauthenticated
     */
    public function handleFacebookCallback(Request $request)
    {
        $socialUser = Socialite::driver('facebook')->stateless()->user();
        $provider = 'facebook';

        $social = DB::table('social_accounts')
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($social) {
            $user = User::where('id', $social->user_id)->first();
        } else {
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'name' => $socialUser->getName() ?? '',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            DB::table('social_accounts')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Auth::login($user);

        $plainToken = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'ok',
            'user' => $user,
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
        ], 200);
    }
}
 