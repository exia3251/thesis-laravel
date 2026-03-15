<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Customer\ShopController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;

// Customer login
Route::get('/shop/login', [AuthController::class, 'customerLoginPage'])->name('customer.login');
Route::post('/shop/login', [AuthController::class, 'customerLogin']);
Route::post('/shop/register', [AuthController::class, 'customerRegister']);

// Public shop page
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

// Customer routes (protected)
Route::middleware('customer')->prefix('shop')->group(function () {
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('shop.checkout');
    Route::get('/orders', [OrderController::class, 'index'])->name('shop.orders');
    Route::get('/profile', [ProfileController::class, 'index'])->name('shop.profile');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');