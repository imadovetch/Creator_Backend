<?php

namespace PacificDev\SocialLogin;
use Illuminate\Http\Request;

use App\Http\Controllers\FrontEnd\UserController;
use Illuminate\Support\Facades\Route;
Route::middleware(['web'])->group(function () {

  Route::get('linkedin/auth', [UserController::class, 'redirectToLinkedIn'])->name('linkedin.auth');
  Route::get('linkedin/callback', [UserController::class, 'handleLinkedInCallback']);
  
});



Route::get('google/auth', [UserController::class, 'redirectToGoogle'])->name('linkedin.auth');
Route::get('login/google/callback', [UserController::class, 'handleGoogleCallback']);

// /*
// |--------------------------------------------------------------------------
// | API Routes
// |--------------------------------------------------------------------------
// |
// | Here is where you can register API routes for your application. These
// | routes are loaded by the RouteServiceProvider within a group which
// | is assigned the "api" middleware group. Enjoy building your API!
// |
// */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//   return $request->user();
// });
// // use App\Http\Controllers\LinkedInController;

// // Route::middleware(['web'])->group(function () {
// //     Route::get('/linkedin/auth', [LinkedInController::class, 'redirectToLinkedIn']);
// //     Route::get('/linkedin/callback', [LinkedInController::class, 'handleLinkedInCallback']);
// // });
// Route::get('linkedin/auth', 'FrontEnd\UserController@redirectToLinkedIn')->name('linkedin.auth');
// // Route::get('linkedin/auth', 'FrontEnd\UserController@onedirect')->name('linkedin.auth');

// Route::get('linkedin/callback', 'FrontEnd\UserController@handleLinkedInCallback');



// // Route::get('login/facebook/callback', 'FrontEnd\UserController@handleFacebookCallback');
// // Route::get('login/google/callback', 'FrontEnd\UserController@handleGoogleCallback');
