<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

// Login with Google (Laravel Socialite). These stay above the React route below.
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// Every other URL that isn't /api loads the React app (resources/views/app.blade.php),
// and React Router decides which page to show.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
