<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public routes
Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{id}', [ShopController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']); 
Route::get('/categories/{id}', [CategoryController::class,'show']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']); // Get all products
Route::get('/products/shop/{shopId}', [ProductController::class, 'getProductsByShop']); // Get products by shop ID
Route::get('/products/category/{categoryId}', [ProductController::class, 'getProductsByCategory']); // Get products by category ID


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/create-user',[AuthController::class,'createUser']);
    Route::get('/admin/shops', [ShopController::class, 'adminIndex']);
    Route::get('/admin/shops/{id}', [ShopController::class, 'adminShow']);
    Route::post('/admin/shops', [ShopController::class, 'store']);
    Route::post('/admin/updateshop/{id}', [ShopController::class, 'update']);
    Route::delete('/admin/deleteshop/{id}', [ShopController::class, 'destroy']);
    Route::put('/user', [UserController::class, 'updateProfile']);
    Route::get('/admin/categories', [CategoryController::class, 'adminIndex']); // Get all categories
    Route::get('/admin/categories/{id}', [CategoryController::class, 'adminShow']); // Get a category by ID
    Route::post('/admin/categories', [CategoryController::class, 'store']); // Create a new category
    Route::post('/admin/updatecategory/{id}', [CategoryController::class, 'update']); // Update a category
    Route::delete('/admin/deletecategory/{id}', [CategoryController::class, 'destroy']); // Delete a category
    Route::post('/admin/products', [ProductController::class, 'store']);
    Route::post('/admin/updateproduct/{id}', [ProductController::class, 'update']);
    Route::delete('/admin/deleteproduct/{id}', [ProductController::class, 'destroy']);
    
});