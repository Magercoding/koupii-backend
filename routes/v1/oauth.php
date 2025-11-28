<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\OauthController;

/**
 * Google OAuth Routes
 */
Route::get('/oauth/google/redirect', [OauthController::class, 'redirectToGoogle']);
Route::get('/oauth/google/callback', [OauthController::class, 'handleGoogleCallback']);
/**
 * Facebook OAuth Routes
 */
Route::get( '/oauth/facebok/redirect', [OauthController::class, 'redirectToFacebook']);
Route::get( '/oauth/facebok/callback', [OauthController::class, 'handleFacebookCallback']);