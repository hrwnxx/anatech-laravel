<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\MyAccountController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/gadgets/{id}', [CatalogController::class, 'show'])->whereNumber('id')->name('catalog.show');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/my/purchases', [MyAccountController::class, 'purchases'])->name('account.purchases');
    Route::get('/my/reviews', [MyAccountController::class, 'reviews'])->name('account.reviews');

    Route::post('/gadgets/{id}/reviews', [CustomerReviewController::class, 'store'])
        ->whereNumber('id')
        ->name('reviews.store');
    Route::patch('/reviews/{review}', [CustomerReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [CustomerReviewController::class, 'destroy'])->name('reviews.destroy');
});

// Admin pages remain on the existing React application.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
