<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Public Category APIs (Accessible by all authenticated users)
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

    // Category APIs (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    });

    // Public Product APIs (Accessible by all authenticated users)
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    // Product APIs (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    });

    // Cart & Order APIs (Customer Only)
    Route::middleware('customer')->group(function () {
        // Cart APIs
        Route::apiResource('cart', CartController::class);

        // Order APIs
        Route::post('/orders', [OrderController::class, 'store'])->middleware('check.stock');
        Route::get('/orders', [OrderController::class, 'index']);

        // Payment APIs
        Route::post('/orders/{order}/payment', [PaymentController::class, 'store']);
        Route::apiResource('payments', PaymentController::class)->only(['show']);
    });

    // Order Status Update API (Admin Only)
    Route::middleware('admin')->group(function () {
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
});
