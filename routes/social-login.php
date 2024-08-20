<?php

namespace PacificDev\SocialLogin;

use App\Http\Controllers\FrontEnd\UserController;
use Illuminate\Support\Facades\Route;

Route::get('linkedin/auth', [UserController::class, 'redirectToLinkedIn'])->name('linkedin.auth');
Route::get('linkedin/callback', [UserController::class, 'handleLinkedInCallback']);



Route::get('google/auth', [UserController::class, 'redirectToGoogle'])->name('linkedin.auth');
Route::get('login/google/callback', [UserController::class, 'handleGoogleCallback']);
