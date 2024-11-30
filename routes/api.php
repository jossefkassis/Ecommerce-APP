<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Public routes
Route::get('/public', function () {
    return 'This is a public route.';
});

Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{id}', [ShopController::class, 'show']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/create-user',[AuthController::class,'createUser']);
    Route::get('/admin/shops', [ShopController::class, 'adminIndex']);
    Route::get('/admin/shops/{id}', [ShopController::class, 'adminShow']);
    Route::post('/admin/shops', [ShopController::class, 'store']);
    Route::put('/admin/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/admin/shops/{id}', [ShopController::class, 'destroy']);
    
    
});

