<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{id}', [ShopController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']); // Get active product by ID
Route::get('/products/shop/{shopId}', [ProductController::class, 'getProductsByShop']); // Get active products by shop ID
Route::get('/products/category/{categoryId}', [ProductController::class, 'getProductsByCategory']); // Get active products by category ID

// Routes for authenticated users
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/updateprofile', [UserController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // User management
    Route::post('/admin/create-user', [AuthController::class, 'createUser']);

    // Shop management
    Route::get('/admin/shops', [ShopController::class, 'adminIndex']);
    Route::get('/admin/shops/{id}', [ShopController::class, 'adminShow']);
    Route::post('/admin/shops', [ShopController::class, 'store']);
    Route::post('/admin/updateshop/{id}', [ShopController::class, 'update']);
    Route::delete('/admin/deleteshop/{id}', [ShopController::class, 'destroy']);

    // Category management
    Route::get('/admin/categories', [CategoryController::class, 'adminIndex']);
    Route::get('/admin/categories/{id}', [CategoryController::class, 'adminShow']);
    Route::post('/admin/categories', [CategoryController::class, 'store']);
    Route::post('/admin/updatecategory/{id}', [CategoryController::class, 'update']);
    Route::delete('/admin/deletecategory/{id}', [CategoryController::class, 'destroy']);

    // Product management
    Route::get('/admin/products', [ProductController::class, 'adminIndex']); // Get all products (including inactive)
    Route::get('/admin/products/{id}', [ProductController::class, 'adminShow']); // Get a product by ID (including inactive)
    Route::post('/admin/products', [ProductController::class, 'store']); // Create a new product
    Route::post('/admin/updateproduct/{id}', [ProductController::class, 'update']); // Update a product
    Route::delete('/admin/deleteproduct/{id}', [ProductController::class, 'destroy']); // Delete a product
});
