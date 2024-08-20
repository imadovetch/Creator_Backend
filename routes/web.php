<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Interface Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
  return view('index');
});
// custom ones linkdeen
Route::get('linkedin/auth', 'FrontEnd\UserController@redirectToLinkedIn')->name('linkedin.auth');
Route::get('linkedin/callback', 'FrontEnd\UserController@handleLinkedInCallback');

require __DIR__ . '/social-login.php';