<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Customer\ShopController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes with Rate Limiting
|--------------------------------------------------------------------------
*/

// Auth routes with stricter rate limiting (5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/auth/customer/login', [AuthController::class, 'customerLogin']);
    Route::post('/auth/customer/register', [AuthController::class, 'customerRegister']);
});

// Logout (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Public routes (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', [ShopController::class, 'getProducts']);
});

// Admin API routes (100 requests per minute for admin operations)
Route::middleware(['admin', 'throttle:100,1'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    // Products
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::get('/products/{id}', [ProductController::class, 'getProduct']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/suppliers/list', [ProductController::class, 'getSuppliers']);
    
    // Inventory
    Route::get('/inventory', [InventoryController::class, 'getInventory']);
    Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn']);
    Route::post('/inventory/stock-out', [InventoryController::class, 'stockOut']);
    Route::get('/inventory/transactions/{productId}', [InventoryController::class, 'getTransactions']);
    
    // Sales
    Route::get('/sales', [SalesController::class, 'getSales']);
    Route::get('/sales/{id}', [SalesController::class, 'getSale']);
    Route::post('/sales', [SalesController::class, 'store']);
    Route::get('/sales/products/list', [SalesController::class, 'getProducts']);
    
    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'getSuppliers']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
    
    // Reports
    Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::get('/reports/inventory', [ReportController::class, 'inventoryReport']);
});

// Customer API routes (60 requests per minute)
Route::middleware(['customer', 'throttle:60,1'])->group(function () {
    // Cart
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/{id}', [CartController::class, 'updateCart']);
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    
    // Orders
    Route::post('/orders', [OrderController::class, 'placeOrder']);
    Route::get('/orders', [OrderController::class, 'getOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [ProfileController::class, 'changePassword']);
});
