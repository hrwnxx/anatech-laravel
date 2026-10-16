<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

/*
| The backend is a JSON API: every page lives in the React app (../frontend).
| The only browser-facing routes are the Google login redirects, because the
| browser itself has to travel to Google and back.
*/
Route::middleware('throttle:google-login')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});
