<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\MyAccountController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

/*
| Every route in this file is prefixed with /api and returns JSON.
| The React app calls these with axios.
*/

// ---- Public: no login needed ------------------------------------------
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/gadgets', [CatalogController::class, 'index']);
Route::get('/gadgets/{id}', [CatalogController::class, 'show'])->whereNumber('id');
Route::get('/categories', [CatalogController::class, 'categories']);
Route::get('/brands', [CatalogController::class, 'brands']);

// ---- Logged in (any role) -----------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/my/purchases', [MyAccountController::class, 'purchases']);
    Route::get('/my/reviews', [MyAccountController::class, 'reviews']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // ---- Admins only ---------------------------------------------------
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class);

        Route::controller(Admin\GadgetController::class)->group(function () {
            Route::get('/gadgets', 'index');
            Route::post('/gadgets', 'store');
            Route::get('/gadgets/{id}', 'show')->whereNumber('id');
            Route::post('/gadgets/{gadget}', 'update');
            Route::patch('/gadgets/{gadget}/archive', 'archive');
            Route::patch('/gadgets/{gadget}/unarchive', 'unarchive');
            Route::post('/gadgets/{gadget}/stock-in', 'stockIn');
            Route::post('/gadgets/{gadget}/stock-out', 'stockOut');
        });

        Route::controller(Admin\PurchaseController::class)->group(function () {
            Route::get('/purchases', 'index');
            Route::post('/purchases', 'store');
            Route::patch('/purchases/{purchase}/archive', 'archive');
            Route::patch('/purchases/{purchase}/unarchive', 'unarchive');
        });

        Route::controller(Admin\ReviewController::class)->group(function () {
            Route::get('/reviews', 'index');
            Route::post('/reviews/bulk', 'bulk');
            Route::patch('/reviews/{review}/approve', 'approve');
            Route::patch('/reviews/{review}/reject', 'reject');
        });

        Route::get('/customers', [Admin\CustomerController::class, 'index']);
        Route::get('/customers/{user}/purchases', [Admin\CustomerController::class, 'purchases']);

        Route::apiResource('users', Admin\UserController::class)->except('show');
        Route::put('/users/{user}/password', [Admin\UserController::class, 'updatePassword']);
    });
});
