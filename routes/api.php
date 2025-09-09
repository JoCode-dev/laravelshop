<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\ProductController as PublicProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);

// Admin routes
Route::middleware('auth:sanctum')->group(function () {
    // Product routes
    Route::apiResource('products', ProductController::class);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Cart routes

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);

    // Payment routes
    Route::post('/payments', [PaymentController::class, 'store']);

    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/users', [DashboardController::class, 'users']);
    Route::get('/dashboard/products', [DashboardController::class, 'products']);
    Route::get('/dashboard/orders', [DashboardController::class, 'orders']);
    Route::get('/dashboard/payments', [DashboardController::class, 'payments']);
    Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/dashboard/sell-stats', [DashboardController::class, 'sellStats']);
});


// User routes
Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{product}', [PublicProductController::class, 'show']);

// Routes publiques (pour les anonymes)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/', [CartController::class, 'store']);
    Route::put('/{id}', [CartController::class, 'update']);
    Route::delete('/{id}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clearCart']);
});

// Routes protégées (pour les utilisateurs connectés)
Route::middleware('auth:sanctum')->group(function () {
    // Migration du panier session vers DB
    Route::post('/cart/migrate', [CartController::class, 'migrateSessionToDatabase']);

    // Autres routes...
});